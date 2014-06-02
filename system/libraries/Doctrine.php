<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
require_once 'system/core/Model.php';

USE Doctrine\Common\ClassLoader,
    Doctrine\ORM\Configuration,
    Doctrine\ORM\EntityManager,
    Doctrine\Common\Cache\ArrayCache,
    Doctrine\DBAL\Logging\EchoSQLLogger,
    Doctrine\ORM\Mapping\Driver\DatabaseDriver,
    Doctrine\ORM\Tools\DisconnectedClassMetadataFactory,
    Doctrine\ORM\Tools\EntityGenerator,
    Doctrine\ORM\Mapping\Driver\AnnotationDriver,       // here down used to read Entity comments
    Doctrine\Common\Annotations\AnnotationReader,
    Doctrine\Common\Annotations\AnnotationRegistry;

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
        $driverImpl = $config->NewDefaultAnnotationDriver([APPPATH.'models/entities']);
        $config->setMetadataDriverImpl($driverImpl);

        $config->setQueryCacheImpl($cache);

        // Proxy configuration
        $config->setProxyDir(APPPATH.'/models/proxies');
        $config->setProxyNamespace('Proxies');

        // Set up logger
        $logger = New EchoSQLLogger;
//         $config->setSQLLogger($logger);

        $config->setAutoGenerateProxyClasses( TRUE );

        // Database connection information
        $connectionOptions = [
            'driver'    => 'pdo_mysql',
            'user'      => $this->db->username,
            'password'  => $this->db->password,
            'host'      => $this->db->hostname,
            'dbname'    => $this->db->database,
        ];

        $driver = new AnnotationDriver(new AnnotationReader(), APPPATH.'models/entities');

        // registering noop annotation autoloader - allow all annotations by default
        AnnotationRegistry::registerLoader('class_exists');
        $config->setMetadataDriverImpl($driver);

        // Create EntityManager
        $this->em = EntityManager::create($connectionOptions, $config);

        /** @var $em \Doctrine\ORM\EntityManager */
        $platform = $this->em->getConnection()->getDatabasePlatform();
        $platform->registerDoctrineTypeMapping('enum', 'string');
    }

    /**
     * generate entity objects automatically from mysql db tables
     * @return none
     */
    public function generateClasses()
    {
        $this->em->getConfiguration()
                 ->setMetadataDriverImpl(
                    New DatabaseDriver(
                            $this->em->getConnection()->getSchemaManager()
                    )
        );

        $cmf = New DisconnectedClassMetadataFactory();
        $cmf->setEntityManager($this->em);
        $metadata = $cmf->getAllMetadata();
        $generator = New EntityGenerator();

        $generator->setUpdateEntityIfExists(true);
        $generator->setGenerateStubMethods(true);
        $generator->setGenerateAnnotations(true);
        $generator->generate($metadata, APPPATH."models/entities");

    }

}