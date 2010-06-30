<?php defined('SYSPATH') or die('No direct script access.');

class Solr_Core_Config {

    /**
     * @var     string  index name
     */
    protected $_index_name;

    /**
     * @var     object  Solr_Field  primary field object
     */
    protected $_primary_field;

    /**
     * @var     string  OR (OR) AND   default query parser, defaults to OR
     */
    protected $_field_query_parser = 'OR';

    /**
     * @var     string  default search field
     */
    protected $_default_search_field;

    /**
     * @var     array   field list
     */
    protected $_fields = array();

    /**
     * @var     array   copy field list
     */
    protected $_copy_fields = array();

    /**
     * Sets the Index/Core name
     *
     * @param   string  $index_name
     * @return  string
     */
    public function index_name($index_name = NULL)
    {
        if ($index_name)
        {
            $this->_index_name = $index_name;

            return $this;
        }
        return $this->_index_name;
    }

    /**
     * @return  array   name => object
    */
    public function fields()
    {
        return $this->_fields;
    }

    public function qp($query_parser = NULL)
    {
        if ($query_parser)
        {
            if ($query_parser !== 'OR' AND $query_parser !== 'AND')
            {
                throw new Solr_Exception('Invalid query parser :input',
                    array(':input' => $query_parser));
            }
            $this->_field_query_parser = $query_parser;

            return $this;
        }
        return $this->_field_query_parser;
    }

    public function pf(Solr_Field $pf = NULL)
    {
        if ($pf)
        {
            $this->_primary_field = $pf;
        }

        return $this->_primary_field;
    }

    public function default_search_field($field_name = NULL)
    {
        if ($field_name)
        {
            $this->_default_search_field = $field_name;
        }
        
        return $this->_default_search_field;
    }

    public function add(Solr_Field $field)
    {
        return $this->add_field($field);
    }

    public function add_field(Solr_Field $field)
    {
        $this->_fields[] = $field;
    }

    public function add_fields(array $fields)
    {
        $this->_fields = array_merge($this->_fields, $fields);
        return $this;
    }

    public function copy_fields($destination, array $fields)
    {
        if (isset($this->_copy_fields[$destination]))
        {
            $this->_copy_fields = array_merge((array)$this->_copy_fields[$dest], $fields);
        }
        else
        {
            $this->_copy_fields = $fields;
        }

        return $this;
    }
}
