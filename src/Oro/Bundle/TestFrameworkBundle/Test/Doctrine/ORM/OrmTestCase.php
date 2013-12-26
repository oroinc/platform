<?php

namespace Oro\Bundle\TestFrameworkBundle\Test\Doctrine\ORM;

use Doctrine\Common\Version;
use Doctrine\Common\EventManager;
use Doctrine\Common\Cache\ArrayCache;
use Doctrine\Common\Annotations\CachedReader;
use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Annotations\AnnotationRegistry;

use Doctrine\ORM\Mapping\Driver\AnnotationDriver;

use Oro\Bundle\TestFrameworkBundle\Test\Doctrine\ORM\Mocks\EntityManagerMock;

/**
 * Base testcase class for all ORM testcases.
 *
 * This class is a clone of Doctrine\Tests\OrmTestCase that is excluded from doctrine package since v2.4.
 */
abstract class OrmTestCase extends \PHPUnit_Framework_TestCase
{
    /**
     * The metadata cache that is shared between all ORM tests (except functional tests).
     */
    private static $metadataCacheImpl = null;

    /**
     * The query cache that is shared between all ORM tests (except functional tests).
     */
    private static $queryCacheImpl = null;

    /**
     * @param array $paths
     * @param mixed $alias
     * @return AnnotationDriver
     */
    protected function createAnnotationDriver($paths = array(), $alias = null)
    {
        if (version_compare(Version::VERSION, '3.0.0', '>=')) {
            $reader = new CachedReader(
                new AnnotationReader(),
                new ArrayCache()
            );
        } else { // if (version_compare(Version::VERSION, '2.2.0-DEV', '>='))
            // Register the ORM Annotations in the AnnotationRegistry
            $reader = new \Doctrine\Common\Annotations\SimpleAnnotationReader();
            $reader->addNamespace('Doctrine\ORM\Mapping');
            $reader = new \Doctrine\Common\Annotations\CachedReader($reader, new ArrayCache());
        }

        AnnotationRegistry::registerFile(__DIR__ . "/../../../lib/Doctrine/ORM/Mapping/Driver/DoctrineAnnotations.php");

        return new \Doctrine\ORM\Mapping\Driver\AnnotationDriver($reader, (array)$paths);
    }

    /**
     * Creates an EntityManager for testing purposes.
     *
     * NOTE: The created EntityManager will have its dependant DBAL parts completely
     * mocked out using a DriverMock, ConnectionMock, etc. These mocks can then
     * be configured in the tests to simulate the DBAL behavior that is desired
     * for a particular test,
     *
     * @param mixed $conn
     * @param EventManager $eventManager
     * @param bool $withSharedMetadata
     * @return EntityManagerMock
     */
    protected function getTestEntityManager($conn = null, $eventManager = null, $withSharedMetadata = true)
    {
        $metadataCache = $withSharedMetadata
            ? self::getSharedMetadataCacheImpl()
            : new \Doctrine\Common\Cache\ArrayCache;

        $config = new \Doctrine\ORM\Configuration();

        $config->setMetadataCacheImpl($metadataCache);
        $config->setMetadataDriverImpl($config->newDefaultAnnotationDriver(array(), true));
        $config->setQueryCacheImpl(self::getSharedQueryCacheImpl());
        $config->setProxyDir(__DIR__ . '/Proxies');
        $config->setProxyNamespace('Doctrine\Tests\Proxies');

        if ($conn === null) {
            $conn = array(
                'driverClass'  => 'Oro\Bundle\TestFrameworkBundle\Test\Doctrine\ORM\Mocks\DriverMock',
                'wrapperClass' => 'Oro\Bundle\TestFrameworkBundle\Test\Doctrine\ORM\Mocks\ConnectionMock',
                'user'         => 'john',
                'password'     => 'wayne'
            );
        }

        if (is_array($conn)) {
            $conn = \Doctrine\DBAL\DriverManager::getConnection($conn, $config, $eventManager);
        }

        return EntityManagerMock::create($conn, $config, $eventManager);
    }

    private static function getSharedMetadataCacheImpl()
    {
        if (self::$metadataCacheImpl === null) {
            self::$metadataCacheImpl = new \Doctrine\Common\Cache\ArrayCache;
        }

        return self::$metadataCacheImpl;
    }

    private static function getSharedQueryCacheImpl()
    {
        if (self::$queryCacheImpl === null) {
            self::$queryCacheImpl = new \Doctrine\Common\Cache\ArrayCache;
        }

        return self::$queryCacheImpl;
    }
}
