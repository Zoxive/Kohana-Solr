##TODO##

Currently a WIP

* Uses Apache Solr extention for PHP (http://www.php.net/manual/en/book.solr.php, http://pecl.php.net/package/solr)
* Generates XML files for different Cores/Indexes
* Each Search Query Returns actual Models (Currently supports Sprig with a "adapter" type of class for easy implementation of other orm systems)

##Core Creation##
* php index.php --uri=solrcli/{modelname}/{method}
* {modelname} is the name of the Solr_Search interface implemented model.
* {method} is the function you want to execute on this model. Current - help/start/stop/rebuild/index/removeall/status
