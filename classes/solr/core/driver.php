<?php

abstract Class Solr_Core_Driver
{
    /*
     * @var $model  Solr_Searchable
     */
    protected $model = NULL;

    public function __construct(Solr_Searchable $model)
    {
        $this->model = $model;
    }

    public function __get($var)
    {
        if (isset($this->$var))
        {
            return $this->$var;
        }

        throw new Solr_Exception(':name does not have an attribute :attribute',
            array
            (
                ':name' => get_class($this),
                ':attribute' => $var
            )
        );
    }

    /**
     * Takes array of docids from solr result, and transforms into the model
     * version of each document. Must keep the same order.
     *
     * Ex:
     *  array(1, 2, 3) -> Array/Object of Models 'Test' 1,2,3
     *
     * @param   array   Array of primarykeys from the solr
     * @return  mixed
     */
    abstract public function in(array $docids);
}
