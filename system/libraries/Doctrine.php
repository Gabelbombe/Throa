<?php
require_once 'system/core/Model.php';

USE Doctrine\Common\ClassLoader,
    Doctrine\ORM\Configuration,
    Doctrine\ORM\EntityManager,
    Doctrine\Common\Cache\ArrayCache,
    Doctrine\DBAL\Logging\EchoSQLLogger;

Class Doctrine Extends CI_Model
{
    public $em = null;

    public function __construct()
    {
        // Set up class loading. You could use different autoloaders, provided by your favorite framework,
        // if you want to.
        require_once APPPATH.'libraries/Doctrine/Common/ClassLoader.php';

        $doctrineClassLoader = New ClassLoader('Doctrine',  APPPATH.'libraries');
        $doctrineClassLoader->register();

        $entitiesClassLoader = New ClassLoader('models', rtrim(APPPATH, "/" ));
        $entitiesClassLoader->register();

        $proxiesClassLoader = New ClassLoader('Proxies', APPPATH.'models/proxies');
        $proxiesClassLoader->register();

        // Set up caches
        $config = New Configuration;
        $cache = New ArrayCache;
        $config->setMetadataCacheImpl($cache);
        $driverImpl = $config->NewDefaultAnnotationDriver([APPPATH.'models/Entities']);
        $config->setMetadataDriverImpl($driverImpl);

        $config->setQueryCacheImpl($cache);

        // Proxy configuration
        $config->setProxyDir(APPPATH.'/models/proxies');
        $config->setProxyNamespace('Proxies');

        // Set up logger
        $logger = New EchoSQLLogger;
        $config->setSQLLogger($logger);

        $config->setAutoGenerateProxyClasses( TRUE );

        // Database connection information
        $connectionOptions = [
            'driver'    => 'pdo_mysql',
            'user'      => $this->db->username,
            'password'  => $this->db->password,
            'host'      => $this->db->hostname,
            'dbname'    => $this->db->database,
        ];

        // Create EntityManager
        $this->em = EntityManager::create($connectionOptions, $config);
    }
}