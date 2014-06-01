<?php

require APPPATH . '/config/autoload.php';

Class AdapterModel
{
    public function __construct()
    {
        print_r($this->load->library('database'));
        die('adapting');
    }

    public function configs()
    {
        return ($foo) ? true : false;
    }

    public function tap()
    {
        return ($foo) ? true : false;
    }
}