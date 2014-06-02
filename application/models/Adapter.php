<?php

Class Adapter Extends CI_Model
{
    private $logger = [];

    public function __construct()
    {
        parent::__construct();
    }

    public static function configs()
    {
        foreach(['username', 'hostname', 'database', 'dbdriver'] AS $handle)
        {
            if (empty($handle)) return false;
        }

        return true;
    }

    public static function addBulkQueue($filter)
    {
        $self = New Self();

        $self->set('filter', $filter)
             ->bulkQueue();
    }

    private function bulkQueue()
    {
        $this->load->model('Queue');

        $em = $this->doctrine->em;

        $batchSize = 20;
        for ($i = 1; $i <= 10000; ++$i)
        {
            $queue = New Queue();

            $queue->setGrant(false)
                  ->setUID($this->logger['filter'][$i]['uid'])
                  ->setAID($this->logger['filter'][$i]['aid'])
                  ->setLOC($this->logger['filter'][$i]['loc']);

            $em->persist($queue);
            if (0 === ($i % $batchSize))
            {
                $em->flush(); // Executes all updates.
                $em->clear(); // Detaches all objects from Doctrine!
            }
        }
    }


    /**
     * Non-Magic otherwise interferes with Doctrine
     * @param $name
     * @param $value
     * @return $this
     */
    public function set($name, $value)
    {
        $this->logger[$name] = $value;
        return $this;
    }

    /**
     * Non-Magic otherwise interferes with Doctrine
     * @param $name
     * @return null|string
     */
    public function get($name)
    {
        if (array_key_exists($name, $this->logger)) return $this->logger[$name];

        $trace = debug_backtrace();
        trigger_error("Undefined property via __get(): $name  in {$trace[0]['file']} on line {$trace[0]['line']}", E_USER_NOTICE);

        return null;
    }
}