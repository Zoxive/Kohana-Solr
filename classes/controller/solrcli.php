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

    /**
     * Creates the Core.
     * Generates required XML files using the models config object and loads it.
     */
    protected function _start()
    {
        $this->solr->create();
    }

    /**
     * Shows the status of the core according to solr
     */
    protected function _status()
    {
    }
}
