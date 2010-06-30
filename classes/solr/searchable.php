<?php defined('SYSPATH') or die('No direct script access.');

interface Solr_Searchable {
    
    /**
     * 
     * 
     * @param   object  Solr_Document 
     * @return  object  Solr_Document
    */
    public function _solr_data(Solr_Document $document);

    /**
     *  
     * 
     * @param   object  Solr_Config
     * @return  object  Solr_Config
    */
    static public function _solr_config(Solr_Config $config);

}
