<?php

namespace Oro\Bundle\TestFrameworkBundle\Test;

use Symfony\Component\Finder\Finder;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase as BaseWebTestCase;
use Oro\Bundle\TestFrameworkBundle\Test\Client;
use Doctrine\ORM\EntityManager;

/**
 * Class WebTestCase
 *
 * @package Oro\Bundle\TestFrameworkBundle\Test
 */
abstract class WebTestCase extends BaseWebTestCase
{
    const DB_ISOLATION = '/@db_isolation(.*)(\r|\n)/U';
    const DB_REINDEX = '/@db_reindex(.*)(\r|\n)/U';

    static protected $db_isolation = false;
    static protected $db_reindex = false;

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
     *
     * @return Client A Client instance
     */
    protected static function createClient(array $options = array(), array $server = array())
    {
        if (!self::$internalClient) {
            self::$internalClient = parent::createClient($options, $server);

            if (self::$db_isolation) {
                /** @var Client $client */
                $client = self::$internalClient;

                //workaround MyISAM search tables are not on transaction
                if (self::$db_reindex) {
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
        }

        return self::$internalClient;
    }

    public static function tearDownAfterClass()
    {
        if (self::$internalClient) {
            /** @var Client $client */
            $client = self::$internalClient;
            if (self::$db_isolation) {
                $client->rollbackTransaction();
                self::$db_isolation = false;
            }
            $client->setSoapClient(null);
            self::$internalClient = null;
        }
    }

    public static function setUpBeforeClass()
    {
        $class = new \ReflectionClass(get_called_class());
        $doc = $class->getDocComment();
        if (preg_match(self::DB_ISOLATION, $doc, $matches) > 0) {
            self::$db_isolation = true;
        } else {
            self::$db_isolation = false;
        }

        if (preg_match(self::DB_REINDEX, $doc, $matches) > 0) {
            self::$db_reindex = true;
        } else {
            self::$db_reindex = false;
        }
    }

    /**
     * @return bool
     */
    public function getIsolation()
    {
        return self::$db_isolation;
    }

    /**
     * @param bool $dbIsolation
     */
    public function setIsolation($dbIsolation = false)
    {
        self::$db_isolation = $dbIsolation;
    }

    public static function getInstance()
    {
        return self::$internalClient;
    }

    /**
     * Attempts to guess the kernel location.
     *
     * When the Kernel is located, the file is required.
     *
     * @return string The Kernel class name
     *
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
