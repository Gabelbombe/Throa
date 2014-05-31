<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

Class WriteController Extends CI_Controller //Implements WriteInterface
{

    private $filter = [];

    public function index()
    {
        $this->filter()->filter();

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


            foreach (array_keys($filter) AS $asset)
            {
                // check for empty based off of filters array keys
                if (! preg_match('/\w/', $asset)) $this->filter = array_filter(array_map('trim', $filter));
            }

            if (! empty($this->filter)) $this->addToQueue();

                unset($this->filter); //oikology
        }

        return $this;
    }

    private function addToQueue()
    {
        if (! empty($this->filter) && count($this->filter) > 2)
        {

        }
        return $this;
    }
}

/* End of file WriteController.php */
/* Location: ./application/controllers/WriteController.php */