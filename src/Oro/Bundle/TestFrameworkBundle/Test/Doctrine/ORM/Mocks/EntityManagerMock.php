<?php

namespace Oro\Bundle\TestFrameworkBundle\Test\Doctrine\ORM\Mocks;

use Doctrine\Common\EventManager;

use Doctrine\ORM\Proxy\ProxyFactory;
use Doctrine\ORM\Configuration;

/**
 * Special EntityManager mock used for testing purposes.
 *
 * This class is a clone of Doctrine\Tests\Mocks\EntityManagerMock that is excluded from doctrine package since v2.4.
 */
class EntityManagerMock extends \Doctrine\ORM\EntityManager
{
    /**
     * @var mixed
     */
    private $uowMock;

    /**
     * @var mixed
     */
    private $proxyFactoryMock;

    /**
     * {@inheritdoc}
     */
    public function getUnitOfWork()
    {
        return isset($this->uowMock) ? $this->uowMock : parent::getUnitOfWork();
    }

    /**
     * @param mixed $uow
     */
    public function setUnitOfWork($uow)
    {
        $this->uowMock = $uow;
    }

    /**
     * @param mixed $proxyFactory
     */
    public function setProxyFactory($proxyFactory)
    {
        $this->proxyFactoryMock = $proxyFactory;
    }

    /**
     * @return ProxyFactory
     */
    public function getProxyFactory()
    {
        return isset($this->proxyFactoryMock) ? $this->proxyFactoryMock : parent::getProxyFactory();
    }

    /**
     * Mock factory method to create an EntityManager.
     *
     * @param mixed $conn
     * @param Configuration $config
     * @param EventManager $eventManager
     * @return EntityManagerMock
     */
    public static function create($conn, Configuration $config = null, EventManager $eventManager = null)
    {
        if (is_null($config)) {
            $config = new Configuration();
            $config->setProxyDir(__DIR__ . '/../Proxies');
            $config->setProxyNamespace('Doctrine\Tests\Proxies');
            $config->setMetadataDriverImpl($config->newDefaultAnnotationDriver(array(), true));
        }
        if (is_null($eventManager)) {
            $eventManager = new EventManager();
        }

        return new EntityManagerMock($conn, $config, $eventManager);
    }
}
