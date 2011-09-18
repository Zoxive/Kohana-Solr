<?php defined('SYSPATH') or die('No direct script access.');

class Solr_Core
{
    /**
     * @var  object  Solr_Driver
     */
    protected $driver = NULL;

    /**
     * @var  Solr_Searchable
     */
    protected $model = NULL;

    /**
     * @var  Solr_Config
     */
    protected $config = NULL;

    /**
     * @var  Kohana_Config_Group  for solr
     */
    static protected $_config = NULL;

    static public function factory(Solr_Searchable $model)
    {
        Solr::getConfig();

        return new Solr($model);
    }

    static public function getClient(array $options = null, $core = null)
    {
        Solr::getConfig();

        if ($options)
        {
            $options = array_merge(Solr::$_config->client_options, $options);
        }
        else
        {
            $options = Solr::$_config->client_options;
        }

        if ($core)
        {
            $options['path'] = $options['path'].'/'.$core;
        }

        $client = new SolrClient($options);
        return $client;
    }

    /**
     * Used for creating cores, and getting their status.
     * I could not figure out how (if possible) with multicores and the Apache solr libray
     *
     * @param  string
     * @param  array
     */
    static public function curl_request($url, array $params = null)
    {
        $cl = curl_init();
        curl_setopt($cl, CURLOPT_URL, $url);
        curl_setopt($cl, CURLOPT_RETURNTRANSFER, true);

        if ($params)
        {
            foreach ($params as $name => $value)
            {
                curl_setopt($cl, $name, $value);
            }
        }

        $response = curl_exec($cl);
        $http_code = curl_getinfo($cl, CURLINFO_HTTP_CODE);
        curl_close($cl);

        if ($http_code != 200)
        {
            throw new Solr_Exception('Invalid Curl Response. HTTP CODE :code',
                array(':code' => $http_code));
        }

        return $response;
    }

    static public function base()
    {
        Solr::getConfig();
        return 'http://'.Solr::$_config->client_options['hostname'].':'.Solr::$_config->client_options['port'].'/'.trim(Solr::$_config->client_options['path'], '/');
    }

    static public function getConfig()
    {
        if (is_null(self::$_config))
        {
            self::$_config = Kohana::$config->load('solr');
        }
    }

    public function __construct(Solr_Searchable $model)
    {
        $driver = Solr::$_config->driver;
        $this->model = $model;
        $this->driver = new $driver($model);
        $this->model_class = get_class($model);
        $this->config = call_user_func($this->model_class.'::_solr_config', new Solr_Config);
    }

    public function __get($var)
    {
        switch($var)
        {
            case 'config':
            case 'driver':
                return $this->$var;
            break;
        }

        throw new Solr_Exception(':name does not have an attribute :attribute',
            array(':name' => get_class($this), ':attribute' => $var));
    }

    public function ping()
    {
        $client = Solr::getClient(null, $this->config->index_name());
        try
        {
            $ping = $client->ping();
        }
        catch (SolrClientException $e)
        {
            return false;
        }
        return $ping->success();
    }

    public function delete()
    {
        if (!$this->ping())
        {
            throw new Solr_Exception('Core ":core" does not exist.',
                array(':core' => $this->config->index_name()));
        }

        $url = Solr::base().'/admin/cores';
        $params = array
        (
            'action'    =>  'UNLOAD',
            'core'      =>  $this->config->index_name(),
        );

        $resp = Solr::curl_request($url.'?'.http_build_query($params));
        $xml = new DOMDocument;
        $xml->formatOutput = true;
        $xml->loadXML($resp);
    }

    public function create()
    {
        if ($this->ping())
        {
            // auto deleting is a little to risky.. so lets throw an error.
            throw new Solr_Exception('Core ":core" exists, delete the previous before recreating',
                array(':core' => $this->config->index_name()));
        }

        // make folder and files
        $this->make();

        $url = Solr::base();
        $path = '/admin/cores';
        $params = array
        (
            'action'        => 'CREATE',
            'name'          => $this->config->index_name(),
            'instanceDir'   => $this->config->index_name().'/',
            'dataDir'       => './data',
        );

        $resp = Solr::curl_request($url.$path.'?'.http_build_query($params));

        $doc = new DOMDocument('1.0');
        $doc->formatOutput = TRUE;
        $doc->loadXML($resp);

        $response = $doc->getElementsByTagName('response') && $doc->getElementsByTagName('response')->item(0)? $doc->getElementsByTagName('response')->item(0) : false;
        if (!$response)
        {
            //throw new Solr_Exception('Invalid Response occured');
            return false;
        }

        $status = $response->getElementsByTagName('int')->item(0);
        $qtime = $response->getElementsByTagName('int')->item(1);
        $core = $response->getElementsByTagName('str')->item(0);

        $obj = new stdClass;
        $obj->status = $status->nodeValue;
        $obj->time = $qtime->nodeValue;
        $obj->core = $core->nodeValue;

        return $obj->status == 0 && $obj->core == $this->config->index_name()? $obj : false;
    }

    /**
     * @throws SolrClientException
     */
    public function remove_all($optimize = true)
    {
        $client = Solr::getClient(null, $this->config->index_name());
        $client->ping();

        $resp = $client->deleteByQuery('*:*'); 
        $client->commit();
        if ($optimize)
        {
            $client->optimize();
        }
    }

    public function create_model_document(Solr_Searchable $model)
    {
        if ( !($model instanceof $this->model))
        {
            throw new Solr_Exception('Model was not an instance of the loaded Config');
        }
        $doc = new SolrInputDocument;
        $doc = $model->_solr_data($doc);

        // fix primary field
        $pf = $this->config->pf();
        // delete if model added the pf
        $doc->deleteField($pf->name);
        // primary fields are prefixed with the core name.. this allows us to use shards
        $value = $this->config->index_name().':'.$model->{$pf->name};
        $doc->addField($pf->name, $value);

        return $doc;
    }

    protected function make()
    {
        $dir = rtrim(Solr::$_config->solr_home, '/').'/';
        $folder = $this->config->index_name();

        $this->make_folder($dir, $folder);
        $this->make_schema($dir.$folder.'/conf/schema.xml');

        $files = Kohana::list_files('solrdefaultfiles');

        $mask = umask(0);
        foreach ($files as $name => $path)
        {
            $name = basename($name);
            copy($path, $dir.$folder.'/conf/'.$name);
        }
        umask($mask);
    }

    protected function make_folder($dir, $folder)
    {

        if (!is_writable($dir))
        {
            throw new Solr_Exception('Solr Home Directory is not writable. :dir',
                array(':dir' => $dir));
        }

        if (!is_dir($dir.$folder))
        {
            $mask = umask(0);
            try
            {
                mkdir($dir.$folder, 0775);
                chown($dir.$folder, Solr::$_config->solr_user);
            }
            catch (ErrorException $e)
            {
                throw new Solr_Exception('Error creating Core Folder in :path'.PHP_EOL.':err',
                    array(':path' => $dir.$folder, ':err' => $e->getMessage()));
            }
            umask($mask);
        }

        if (!is_dir($dir.$folder.'/conf'))
        {
            $mask = umask(0);
            try
            {
                mkdir($dir.$folder.'/conf', 0775);
                chown($dir.$folder.'/conf', Solr::$_config->solr_user);
            }
            catch (ErrorException $e)
            {
                throw new Solr_Exception('Error creating Core Folder in :path'.PHP_EOL.':err',
                    array(':path' => $dir.$folder.'/conf', ':err' => $e->getMessage()));
            }
            umask($mask);
        }
    }

    protected function make_schema($file)
    {
        $xml = new DOMDocument('1.0', 'UTF-8');
        $xml->formatOutput = true;

        // schema
        $schema = $xml->createElement('schema');
        $xml->appendChild($schema);
        $schema->setAttribute('name', $this->config->index_name());
        $schema->setAttribute('version', '1.2');

        // hi
        $comment = 'Generated '.date('r').' | Using https://github.com/Zoxive/Kohana-Solr';
        $schema->appendChild(new DOMComment($comment));

        // types
        $types = $xml->createElement('types');
        $schema->appendChild($types);

        $add_type = array();
        foreach ($this->config->get_fields() as $field)
        {
            $class = get_class($field);
            if (in_array($class, $add_type))
            {
                continue;
            }
            $fieldType = call_user_func($class.'::generateXML', $xml->createElement('fieldType'));
            $types->appendChild($fieldType);

            $add_type[] = $class;
        }

        // fields
        $fields = $xml->createElement('fields');
        $schema->appendChild($fields);

        $created_fields = array();

        foreach ($this->config->get_fields() as $field)
        {
            $fields->appendChild($field->generateFieldXML($xml->createElement($field->tag_name())));
            $created_fields[] = $field->name;
        }

        // UNIQUE
        $schema->appendChild($xml->createElement('uniqueKey', $this->config->pf()->name));

        // Default Search Field
        $default_search_field = $this->config->default_search_field();
        if (empty($default_search_field))
        {
            throw new Solr_Exception('Default Search Field can not be empty.');
        }
        elseif (!in_array($default_search_field, $created_fields))
        {
            throw new Solr_Exception('Default search field ":field" not found.',
                array(':field' => $default_search_field));
        }
        $schema->appendChild($xml->createElement('defaultSearchField', $default_search_field));

        // default query parser
        $schema->appendChild($xml->createElement('solrQueryParser', $this->config->qp()));

        // copyFields
        foreach ($this->config->get_copy_fields() as $dest => $fields)
        {
            if (!in_array($dest, $created_fields))
            {
                throw new Solr_Exception('Can not copy fields to destination that does not exist for field ":dest".',
                    array(':dest' => $dest));
            }
            $schema->appendChild(new DOMComment('copyFields for Destination "'.$dest.'"'));
            foreach ($fields as $field)
            {
                $copy_field = $xml->createElement('copyField');
                $copy_field->setAttribute('source', $field);
                $copy_field->setAttribute('dest', $dest);

                $schema->appendChild($copy_field);
            }
        }

        $xml = $xml->saveXML();

        $mask = umask(0);
        $res = file_put_contents($file, $xml);
        umask($mask);

        return $res;
    }
}
