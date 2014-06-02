<?php

Class AdapterModel
{
    public function __construct()
    {
        $this->load->library('database');

//        print_r($this->db->query('SELECT * FROM fls_approved'));
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