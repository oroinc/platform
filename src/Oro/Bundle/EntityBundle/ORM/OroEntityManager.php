<?php

namespace Oro\Bundle\EntityBundle\ORM;

use Doctrine\Common\EventManager;
use Doctrine\Common\Proxy\AbstractProxyFactory;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception\DriverException;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\ClassMetadataFactory;
use Doctrine\ORM\ORMException;
use Doctrine\ORM\Proxy\ProxyFactory;
use Doctrine\ORM\UnitOfWork;
use Doctrine\ORM\Utility\IdentifierFlattener;
use Oro\Bundle\EntityBundle\ORM\Event\PreCloseEventArgs;
use Psr\Log\LoggerInterface;

/**
 * This entity manager has the following improvements:
 * * adds "preClose" event
 * * adds the default lifetime of cached ORM queries
 * * adds a possibility to use custom factory for metadata
 */
class OroEntityManager extends EntityManager
{
    private const SQL_STATE_NUMERIC_VALUE_OUT_OF_RANGE = '22003';

    /** @var int|null */
    private $defaultQueryCacheLifetime = false;

    /** @var LoggerInterface */
    private $logger;

    /**
     * @param LoggerInterface $logger
     */
    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * {@inheritdoc}
     */
    public static function create($conn, Configuration $config, EventManager $eventManager = null)
    {
        if (!$config->getMetadataDriverImpl()) {
            throw ORMException::missingMappingDriverImpl();
        }

        if (is_array($conn)) {
            $conn = \Doctrine\DBAL\DriverManager::getConnection($conn, $config, ($eventManager ? : new EventManager()));
        } elseif ($conn instanceof Connection) {
            if ($eventManager !== null && $conn->getEventManager() !== $eventManager) {
                throw ORMException::mismatchedEventManager();
            }
        } else {
            throw new \InvalidArgumentException('Invalid argument: ' . $conn);
        }

        return new static($conn, $config, $conn->getEventManager());
    }

    /**
     * {@inheritdoc}
     */
    public function find($entityName, $id, $lockMode = null, $lockVersion = null)
    {
        try {
            return parent::find($entityName, $id, $lockMode, $lockVersion);
        } catch (DriverException $e) {
            return $this->handleDriverException($e, $entityName, $id);
        }
    }

    /**
     * Sets the Metadata factory service instead of create the factory in the manager constructor.
     *
     * @param ClassMetadataFactory $metadataFactory
     */
    public function setMetadataFactory(ClassMetadataFactory $metadataFactory)
    {
        $metadataFactory->setEntityManager($this);
        $metadataFactory->setCacheDriver($this->getConfiguration()->getMetadataCacheImpl());

        // $this->metadataFactory = $metadataFactory;
        $this->setPrivateMetadataFactory(
            EntityManager::class,
            $this,
            $metadataFactory
        );
        // $this->getProxyFactory()->metadataFactory = $metadataFactory;
        $this->setPrivateMetadataFactory(
            AbstractProxyFactory::class,
            $this->getProxyFactory(),
            $metadataFactory
        );
        // $this->getProxyFactory()->identifierFlattener->metadataFactory = $metadataFactory;
        $this->setPrivateMetadataFactory(
            IdentifierFlattener::class,
            $this->getPrivateIdentifierFlattener(ProxyFactory::class, $this->getProxyFactory()),
            $metadataFactory
        );
        // $this->getUnitOfWork()->identifierFlattener->metadataFactory = $metadataFactory;
        $this->setPrivateMetadataFactory(
            IdentifierFlattener::class,
            $this->getPrivateIdentifierFlattener(UnitOfWork::class, $this->getUnitOfWork()),
            $metadataFactory
        );
    }

    /**
     * {@inheritdoc}
     */
    public function close()
    {
        $evm = $this->getEventManager();
        if ($evm->hasListeners(Events::preClose)) {
            $evm->dispatchEvent(Events::preClose, new PreCloseEventArgs($this));
        }

        parent::close();
    }

    /**
     * {@inheritdoc}
     */
    public function createQuery($dql = '')
    {
        $query = parent::createQuery($dql);
        if (false === $this->defaultQueryCacheLifetime) {
            $config = $this->getConfiguration();
            $this->defaultQueryCacheLifetime = $config instanceof OrmConfiguration
                ? $config->getAttribute('DefaultQueryCacheLifetime')
                : null;
        }
        $query->setQueryCacheLifetime($this->defaultQueryCacheLifetime);

        return $query;
    }

    /**
     * @param DriverException $exception
     * @param string          $entityName
     * @param mixed           $id
     *
     * @return mixed
     *
     * @throws DriverException
     */
    private function handleDriverException(DriverException $exception, string $entityName, $id)
    {
        // handle the situation when we try to get the entity with id that the database doesn't support
        if ($exception->getSQLState() === self::SQL_STATE_NUMERIC_VALUE_OUT_OF_RANGE) {
            if ($this->logger) {
                $this->logger->warning(sprintf(
                    'Out of range value "%s" for identity column of the "%s" entity.',
                    $id,
                    $entityName
                ));
            }

            return null;
        }

        throw $exception;
    }

    /**
     * @param string $class
     * @param object $object
     *
     * @return object
     */
    private function getPrivateIdentifierFlattener($class, $object)
    {
        $property = new \ReflectionProperty($class, 'identifierFlattener');
        $property->setAccessible(true);

        return $property->getValue($object);
    }

    /**
     * @param string               $class
     * @param object               $object
     * @param ClassMetadataFactory $metadataFactory
     */
    private function setPrivateMetadataFactory($class, $object, ClassMetadataFactory $metadataFactory)
    {
        $property = new \ReflectionProperty($class, 'metadataFactory');
        $property->setAccessible(true);
        $property->setValue($object, $metadataFactory);
    }
}
