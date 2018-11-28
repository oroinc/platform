<?php

namespace Oro\Bundle\EntityBundle\DataCollector;

use Oro\Bundle\EntityBundle\ORM\OrmConfiguration;
use Oro\Bundle\EntityBundle\ORM\OroEntityManager;

/**
 * Collects profiling information about ORM operations.
 */
class ProfilingEntityManager extends OroEntityManager
{
    /** @var OrmLogger */
    private $logger;

    /** @var array */
    private $loggingHydrators;

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
        $logger = $this->getProfilingLogger();
        if ($logger) {
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
        $logger = $this->getProfilingLogger();
        if ($logger) {
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
        $logger = $this->getProfilingLogger();
        if ($logger) {
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
        $logger = $this->getProfilingLogger();
        if ($logger) {
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
        $logger = $this->getProfilingLogger();
        if ($logger) {
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
        $logger = $this->getProfilingLogger();
        if ($logger) {
            $logger->startFlush();
            parent::flush($entity);
            $logger->stopFlush();
        } else {
            parent::flush($entity);
        }
    }

    /**
     * Gets logging hydrators are used for a profiling.
     *
     * @return array
     */
    private function getLoggingHydrators()
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
    private function getProfilingLogger()
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
