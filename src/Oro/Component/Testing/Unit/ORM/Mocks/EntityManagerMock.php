<?php

namespace Oro\Component\Testing\Unit\ORM\Mocks;

use Doctrine\Common\EventManager;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\ORMInvalidArgumentException;
use Doctrine\ORM\Proxy\ProxyFactory;
use Symfony\Component\Filesystem\Filesystem;

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

    public function __destruct()
    {
        $fs = new Filesystem();
        if ($fs->exists(__DIR__ . '/../Proxies')) {
            $fs->remove(__DIR__ . '/../Proxies');
        }
    }

    #[\Override]
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
    #[\Override]
    public function getProxyFactory()
    {
        return isset($this->proxyFactoryMock) ? $this->proxyFactoryMock : parent::getProxyFactory();
    }

    /**
     * Mock factory method to create an EntityManager.
     *
     * @param mixed $conn
     * @param Configuration|null $config
     * @param EventManager|null $eventManager
     * @return EntityManagerMock
     */
    #[\Override]
    public static function create($conn, ?Configuration $config = null, ?EventManager $eventManager = null)
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

    #[\Override]
    public function flush($entity = null)
    {
        $this->getUnitOfWork()->commit($entity);
    }

    #[\Override]
    public function persist($entity)
    {
        if (!is_object($entity)) {
            throw ORMInvalidArgumentException::invalidObject('EntityManager#persist()', $entity);
        }

        $this->getUnitOfWork()->persist($entity);
    }
}
