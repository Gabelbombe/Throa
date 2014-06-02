<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Run extends CI_Controller {

    public function index()
    {
        $this->load->helper('form');
        $this->load->view('run.php');
    }
}

/* End of file Run.php */
/* Location: ./application/controllers/Run.php */