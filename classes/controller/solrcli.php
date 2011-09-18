<?php

class Controller_SolrCLI extends Controller
{
    protected $model = NULL;
    protected $solr = NULL;

    public function before()
    {
        if (!Kohana::$is_cli)
        {
            header('Location: '.Kohana::base());
            exit();
        }
        // we use this in cli to kill output buffering
        // allows us to output real time instead of when everything is done processing
        ob_end_flush();

        parent::before();
    }

    public function action_index()
    {
        $model_name = $this->request->param('model');
        $method = $this->request->param('method', 'status');

        if (empty($model_name) || $method == 'help' || $model_name == 'help')
        {
            $this->_help();
        }

        // todo make this use the driver class somehow
        $this->model = Sprig::factory($model_name);
        $this->solr = Solr::factory($this->model); 

        if (method_exists($this, '_'.$method))
        {
            $this->{'_'.$method}();
        }
        else
        {
            throw new Kohana_Exception('Core method: :method not found.', array(':method' => $method === NULL? 'NULL' : $method));
        }
    }

    protected function _help()
    {
        echo 
'----------Help---------
* Use - solrcli/{model}/{method}
*
* Methods
* start     (makes core)
* stop      (unloads core)
* rebuild   (deletes all documents, unloads core, starts core)
* index     (indexes all rows in the database. Not recommended for production use)
* removeall (deletes all documents in the core)
* status    (shows core status)
';
        echo PHP_EOL;
        exit();
    }

    /**
     * Creates the Core.
     * Generates required XML files using the models config object and loads it.
     */
    protected function _start()
    {
        if (($resp = $this->solr->create()))
        {
            echo 'Created Core - "', $resp->core, '"', ' (', $resp->time, ')';
            echo PHP_EOL;
        }
        else
        {
            echo 'Failed creating Core';
        }
    }

    protected function _stop()
    {
        try
        {
            $this->solr->delete();
        }
        catch (Solr_Exception $e)
        {
            echo 'Err:', PHP_EOL, $e->getMessage(), PHP_EOL;
        }
        if (!isset($e))
        {
            echo 'Stopped Core - Successfully', PHP_EOL;
        }
    }
    protected function _rebuild()
    {
        $this->_removeall();

        $this->_stop();
        $this->_start();
        $this->_index();
    }
    protected function _removeall()
    {
        try
        {
            $this->solr->remove_all();
        }
        catch (SolrClientException $e)
        {
            echo $e->getMessage(), PHP_EOL;
        }
    }
    /**
     * Being that this is a quick dirty way and should not be used in production
     * This is not a function available in the solr class
     */
    protected function _index()
    {
        $rows = $this->solr->driver->get_all();

        $client = Solr::getClient(null, $this->solr->config->index_name());
        $client->ping();

        $i = 1;
        foreach ($rows as $row)
        {
            $doc = $this->solr->create_model_document($row);
            $client->addDocument($doc);
            // todo remove this
            if ($i++ == 50) break;
        }
        $client->commit();
        $client->optimize();
    }

    /**
     * Shows the status of the core according to solr
     */
    protected function _status()
    {
        $url = Solr::base().'/admin/cores?';

        $params = array('action' => 'STATUS', 'core' => $this->solr->config->index_name());

        $resp = Solr::curl_request($url.http_build_query($params));

        $xml = new DOMDocument('1.0', 'UTF-8');
        $xml->loadXML($resp);

        $this->show_status($xml, $this->solr->config->index_name());
    }

    protected function show_status(DOMDocument $doc, $core = null)
    {
        $doc->formatOutput = TRUE;
        $main_lst = $doc->getElementsByTagName('lst')->item(1);

        foreach ($main_lst->childNodes as $lst)
        {
            if ($core && $lst->getAttribute('name') != $core) continue;

            $name           = $lst->childNodes->item(0);
            $instance_dir   = $lst->childNodes->item(1);
            $data_dir       = $lst->childNodes->item(2);
            $start_time     = $lst->childNodes->item(3);
            $uptime         = $lst->childNodes->item(4);
            $index          = $lst->childNodes->item(5);

            if (!$index)
            {
                echo 'No Core "', $core, '" Exists', PHP_EOL;
                exit();
            }

            $num_docs       = $index->childNodes->item(0);
            $max_doc        = $index->childNodes->item(1);
            $version        = $index->childNodes->item(2);
            $optimized      = $index->childNodes->item(3);
            $current        = $index->childNodes->item(4);
            $has_deletions  = $index->childNodes->item(5);
            $directory      = $index->childNodes->item(6);
            $last_modified  = $index->childNodes->item(7);

            $date_format = 'F j, Y, g:i a P';

            $start_time_formated = date($date_format, strtotime($start_time->nodeValue));
            $last_modified_formated = date($date_format, strtotime($last_modified->nodeValue));

            $time   = (int)$uptime->nodeValue;
            $secs   = intval($time / 1000);
            $hrs    = intval(($secs / 3600) % 24);
            $days   = intval($secs / 3600 / 24);
            $mins   = intval(($secs / 60) % 60);

            $uptime_formated = ($days > 0? $days.' days ' : '').($hrs>0? $hrs.' hours ' : '').$mins.' minutes ';

            echo
"------------{$name->nodeValue}------------
    Index Name:     {$name->nodeValue}
    Instance Dir:   {$instance_dir->nodeValue}
    Data Dir:       {$data_dir->nodeValue}
    Start Time:     {$start_time_formated}
    Up Time:        {$uptime_formated}
        --------- Index Data ---------
        Num Docs:       {$num_docs->nodeValue}
        Max Docs:       {$max_doc->nodeValue}
        Version:        {$version->nodeValue}
        Optimized:      {$optimized->nodeValue}
        Current:        {$current->nodeValue}
        Has Deletions:  {$has_deletions->nodeValue}
        Directory:      {$directory->nodeValue}
        Last Modified   {$last_modified_formated}
";
        }
    }
}
