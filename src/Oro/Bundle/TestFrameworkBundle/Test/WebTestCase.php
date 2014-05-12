<?php

namespace Oro\Bundle\TestFrameworkBundle\Test;

use Symfony\Component\Finder\Finder;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase as BaseWebTestCase;

use Doctrine\ORM\EntityManager;

use Oro\Bundle\TestFrameworkBundle\Test\Client;

/**
 * Class WebTestCase
 *
 * @package Oro\Bundle\TestFrameworkBundle\Test
 */
abstract class WebTestCase extends BaseWebTestCase
{
    const DB_ISOLATION_ANNOTATION = 'dbIsolation';
    const DB_REINDEX_ANNOTATION = 'dbReindex';

    /**
     * @var bool[]
     */
    static private $dbIsolation;

    /**
     * @var bool[]
     */
    static private $dbReindex;

    /**
     * @var Client
     */
    static protected $internalClient;

    protected function tearDown()
    {
        $refClass = new \ReflectionClass($this);
        foreach ($refClass->getProperties() as $prop) {
            if (!$prop->isStatic() && 0 !== strpos($prop->getDeclaringClass()->getName(), 'PHPUnit_')) {
                $prop->setAccessible(true);
                $prop->setValue($this, null);
            }
        }
    }

    /**
     * Creates a Client.
     *
     * @param array $options An array of options to pass to the createKernel class
     * @param array $server  An array of server parameters
     * @return Client A Client instance
     */
    protected static function createClient(array $options = array(), array $server = array())
    {
        if (!self::$internalClient) {
            self::$internalClient = parent::createClient($options, $server);

            if (self::getDbIsolationSetting()) {
                /** @var Client $client */
                $client = self::$internalClient;

                //workaround MyISAM search tables are not on transaction
                if (self::getDbIsolationSetting()) {
                    $kernel = $client->getKernel();
                    $application = new \Symfony\Bundle\FrameworkBundle\Console\Application($kernel);
                    $application->setAutoExit(false);
                    $options = array('command' => 'oro:search:reindex');
                    $options['--env'] = "test";
                    $options['--quiet'] = null;
                    $application->run(new \Symfony\Component\Console\Input\ArrayInput($options));
                }

                $client->startTransaction();
                $pdoConnection = Client::getPdoConnection();
                if ($pdoConnection) {
                    //set transaction level to 1 for entityManager
                    $connection = $client->createConnection($pdoConnection);
                    $client->getContainer()->set('doctrine.dbal.default_connection', $connection);

                    /** @var EntityManager $entityManager */
                    $entityManager = $client->getContainer()->get('doctrine.orm.entity_manager');
                    if (spl_object_hash($entityManager->getConnection()) != spl_object_hash($connection)) {
                        $reflection = new \ReflectionProperty('Doctrine\ORM\EntityManager', 'conn');
                        $reflection->setAccessible(true);
                        $reflection->setValue($entityManager, $connection);
                    }
                }
            }
        } else {
            self::$internalClient->setServerParameters($server);
        }

        return self::$internalClient;
    }

    public static function tearDownAfterClass()
    {
        if (self::$internalClient) {
            /** @var Client $client */
            $client = self::$internalClient;
            if (self::getDbIsolationSetting()) {
                $client->rollbackTransaction();
            }
            $client->setSoapClient(null);
            self::$internalClient = null;
        }
    }

    /**
     * Get value of dbIsolation option from annotation of called class
     *
     * @return bool
     */
    protected static function getDbIsolationSetting()
    {
        $calledClass = get_called_class();
        if (!isset(self::$dbIsolation[$calledClass])) {
            self::$dbIsolation[$calledClass] = self::isClassHasAnnotation($calledClass, self::DB_ISOLATION_ANNOTATION);
        }

        return self::$dbIsolation[$calledClass];
    }

    /**
     * Get value of dbIsolation option from annotation of called class
     *
     * @return bool
     */
    protected static function getDbReindexSetting()
    {
        $calledClass = get_called_class();
        if (!isset(self::$dbReindex[$calledClass])) {
            self::$dbReindex[$calledClass] = self::isClassHasAnnotation($calledClass, self::DB_REINDEX_ANNOTATION);
        }

        return self::$dbReindex[$calledClass];
    }

    /**
     * @param string $className
     * @param string $annotationName
     * @return bool
     */
    protected static function isClassHasAnnotation($className, $annotationName)
    {
        $annotations = \PHPUnit_Util_Test::parseTestMethodAnnotations($className);
        return isset($annotations['class'][$annotationName]);
    }

    /**
     * @return Client
     * @throws \BadMethodCallException
     */
    public static function getClientInstance()
    {
        if (!self::$internalClient) {
            throw new \BadMethodCallException('Client instance is not initialized.');
        }

        return self::$internalClient;
    }

    /**
     * Attempts to guess the kernel location.
     *
     * When the Kernel is located, the file is required.
     *
     * @return string The Kernel class name
     * @throws \RuntimeException
     */
    protected static function getKernelClass()
    {
        $dir = isset($_SERVER['KERNEL_DIR']) ? $_SERVER['KERNEL_DIR'] : static::getPhpUnitXmlDir();

        $finder = new Finder();
        $finder->name('AppKernel.php')->depth(0)->in($dir);
        $results = iterator_to_array($finder);
        if (!count($results)) {
            throw new \RuntimeException(
                'Either set KERNEL_DIR in your phpunit.xml according to ' .
                'http://symfony.com/doc/current/book/testing.html#your-first-functional-test ' .
                'or override the WebTestCase::createKernel() method.'
            );
        }

        $file = current($results);
        $class = $file->getBasename('.php');

        require_once $file;

        return $class;
    }
}
