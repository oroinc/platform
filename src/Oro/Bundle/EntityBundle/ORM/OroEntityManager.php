<?php

namespace Oro\Bundle\EntityBundle\ORM;

use Doctrine\Common\EventManager;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\ClassMetadataFactory;
use Doctrine\ORM\ORMException;
use Oro\Bundle\EntityBundle\DataCollector\OrmLogger;
use Oro\Bundle\EntityBundle\ORM\Event\PreCloseEventArgs;

/**
 * @todo: think to replace this class with two decorators, one for override 'close' method, another for a profiling
 */
class OroEntityManager extends EntityManager
{
    /** @var OrmLogger */
    protected $logger;

    /** @var array */
    protected $loggingHydrators;

    /** @var int|null */
    protected $defaultQueryCacheLifetime;

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
            throw new \InvalidArgumentException("Invalid argument: " . $conn);
        }

        return new OroEntityManager($conn, $config, $conn->getEventManager());
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

        $reflProperty = new \ReflectionProperty(EntityManager::class, 'metadataFactory');
        $reflProperty->setAccessible(true);
        $reflProperty->setValue($this, $metadataFactory);
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
    public function newHydrator($hydrationMode)
    {
        $hydrators = $this->getLoggingHydrators();
        if (isset($hydrators[$hydrationMode])) {
            $className = $hydrators[$hydrationMode]['loggingClass'];
            if (class_exists($className)) {
                return new $className($this);
            }
        }

        return parent::newHydrator($hydrationMode);
    }

    /**
     * {@inheritdoc}
     */
    public function persist($entity)
    {
        if ($logger = $this->getProfilingLogger()) {
            $logger->startPersist();
            parent::persist($entity);
            $logger->stopPersist();
        } else {
            parent::persist($entity);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function detach($entity)
    {
        if ($logger = $this->getProfilingLogger()) {
            $logger->startDetach();
            parent::detach($entity);
            $logger->stopDetach();
        } else {
            parent::detach($entity);
        }
    }


    /**
     * {@inheritdoc}
     */
    public function merge($entity)
    {
        if ($logger = $this->getProfilingLogger()) {
            $logger->startMerge();
            $mergedEntity = parent::merge($entity);
            $logger->stopMerge();

            return $mergedEntity;
        } else {
            return parent::merge($entity);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function refresh($entity)
    {
        if ($logger = $this->getProfilingLogger()) {
            $logger->startRefresh();
            parent::refresh($entity);
            $logger->stopRefresh();
        } else {
            parent::refresh($entity);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function remove($entity)
    {
        if ($logger = $this->getProfilingLogger()) {
            $logger->startRemove();
            parent::remove($entity);
            $logger->stopRemove();
        } else {
            parent::remove($entity);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function flush($entity = null)
    {
        if ($logger = $this->getProfilingLogger()) {
            $logger->startFlush();
            parent::flush($entity);
            $logger->stopFlush();
        } else {
            parent::flush($entity);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function createQuery($dql = '')
    {
        return parent::createQuery($dql)->setQueryCacheLifetime($this->defaultQueryCacheLifetime);
    }

    /**
     * @param int|null
     */
    public function setDefaultQueryCacheLifetime($defaultQueryCacheLifetime)
    {
        $this->defaultQueryCacheLifetime = $defaultQueryCacheLifetime;
    }

    /**
     * Gets logging hydrators are used for a profiling.
     *
     * @return array
     */
    protected function getLoggingHydrators()
    {
        if (is_array($this->loggingHydrators)) {
            return $this->loggingHydrators;
        }

        $config = $this->getConfiguration();

        $this->loggingHydrators = $config instanceof OrmConfiguration
            ? $config->getAttribute('LoggingHydrators', [])
            : [];

        return $this->loggingHydrators;
    }

    /**
     * Gets a profiling logger.
     *
     * @return OrmLogger|null
     */
    protected function getProfilingLogger()
    {
        if ($this->logger) {
            return $this->logger;
        }

        if (false === $this->logger) {
            return null;
        }

        $config = $this->getConfiguration();

        $this->logger = $config instanceof OrmConfiguration
            ? $config->getAttribute('OrmProfilingLogger', false)
            : false;

        return $this->logger;
    }
}
