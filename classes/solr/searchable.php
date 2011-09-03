<?php defined('SYSPATH') or die('No direct script access.');

interface Solr_Searchable
{
    /**
     * @param   object  SolrInputDocument
     * @return  object  SolrInputDocument
    */
    public function _solr_data(SolrInputDocument $document);

    /**
     * @param   object  Solr_Config
     * @return  object  Solr_Config
    */
    static public function _solr_config(Solr_Config $config);
}
