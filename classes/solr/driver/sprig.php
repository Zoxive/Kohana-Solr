<?php defined('SYSPATH') or die('No direct script access.');

class Solr_Driver_Sprig extends Solr_Driver
{
    public function in(array $docids)
    {
        $query = DB::select()
            ->where($this->model->pk(), 'IN', $docids)
            ->order_by(DB::expr('FIELD(`'.$this->model->pk().'`, '.implode(',', $docids).')'));
        return $this->model->load($query, NULL);
    }

    public function get_all()
    {
        return $this->model->load(NULL, NULL);
    }
}
