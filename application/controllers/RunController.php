<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Run extends CI_Controller {

    public function index()
    {
        $this->load->view('run_message');
    }
}

/* End of file run.php */
/* Location: ./application/controllers/run.php */