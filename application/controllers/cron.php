<?php

Class Cron Extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();

        // ......
    }

    public function index()
    {
        return false;
    }

    public function run($task)
    {
        if(! $this->input->is_cli_request()) die('CLI Access only....');

        if (method_exists(__CLASS__, $task) && is_callable([__CLASS__, $task]))
        {
            call_user_func([__CLASS__, $task]);
        } else {
            die("Method: $task does not exist....");
        }
    }

    protected function update() {}
    protected function add() {}
}
