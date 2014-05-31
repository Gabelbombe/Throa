<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class RunController extends CI_Controller {

    public function index()
    {
        $this->load->helper('form');
        $this->load->view('RunView.php');
    }
}

/* End of file RunController.php */
/* Location: ./application/controllers/RunController.php */