<?php

Class Adapter Extends CI_Model
{
    private $logger = [],
            $em     = false;

    public function __construct()
    {
        parent::__construct();


        $doctrine = New Doctrine();
        print_r($doctrine->generateClasses());

        $this->em = $this->doctrine->em;
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

        $self->set('filter', [ $filter ])
             ->bulkQueue();
    }

    private function bulkQueue()
    {
        $this->load->model('Entities/FlsQueue');

        $batchSize = 20;
        for ($i = 0; $i <= count($this->logger['filter']); ++$i)
        {
            $queue = New FlsQueue();


            $queue->setGrant(false)
                  ->setUid($this->logger['filter'][$i]['uid'])
                  ->setAid($this->logger['filter'][$i]['aid'])
                  ->setLoc($this->logger['filter'][$i]['loc']);

            try {
                //save to database
                $this->em->persist($queue);

                if (0 === ($i % $batchSize))
                {
                    $this->em->flush(); // Executes all updates.
                    $this->em->clear(); // Detaches all objects from Doctrine!
                }
            } catch(Exception $e) {
                Throw New PDOException($e->getMessage());
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