<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class WriteController extends CI_Controller
{

    private $filter = [];

    public function index()
    {
        $this->filter();

        $this->load->helper('url');
        redirect('/', 'refresh');
    }

    private function filter()
    {
        if (isset($_POST) && ! empty($_POST))
        {
            $filter = filter_input_array(INPUT_POST, [
                'uid' => FILTER_SANITIZE_ENCODED,
                'aid' => FILTER_SANITIZE_ENCODED,
            ]);

            if (! preg_match('\s', $filter))
                $this->filter = array_filter(array_map('trim', $filter));

            echo 'reg: ' .  print_r($filter,1);
            echo 'this: '.  print_r($this->filter,1);
            die;

        }

        return false;
    }
}

/* End of file WriteController.php */
/* Location: ./application/controllers/WriteController.php */