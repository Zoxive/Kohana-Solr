<?php defined('SYSPATH') or die('No direct script access.');

return array
(
    /*
     * Class used for the conversion of document ids into a database object of models
     */
    'driver' => 'Solr_Driver_Sprig',
    /*
     * This is where files will be created for each index/core
     */
    'solr_home' => '/usr/share/solr/',

    'client_options' => array
    (
        'hostname'  => 'localhost',
        'port'      => '8080',
        'path'      => 'solr'
    ),
);
