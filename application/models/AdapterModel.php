<?php

Class AdapterModel Extends CI_Model
{
    public function __construct()
    {
        parent::__construct();

       print_r($this->db->query('SELECT * FROM fls_approved'));
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