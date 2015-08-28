<?php

namespace Oro\Bundle\EntityBundle\DataCollector;

use Oro\Bundle\EntityBundle\ORM\OroEntityManager;

class LoggingEntityManager extends OroEntityManager
{
    const CLASS_NAME = 'Oro\Bundle\EntityBundle\DataCollector\LoggingEntityManager';

    /**
     * {@inheritdoc}
     */
    public function newHydrator($hydrationMode)
    {
        $hydrators = $this->getLoggingConfiguration()->getLoggingHydrators();
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
        if ($logger = $this->getLoggingConfiguration()->getOrmProfilingLogger()) {
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
        if ($logger = $this->getLoggingConfiguration()->getOrmProfilingLogger()) {
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
        if ($logger = $this->getLoggingConfiguration()->getOrmProfilingLogger()) {
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
        if ($logger = $this->getLoggingConfiguration()->getOrmProfilingLogger()) {
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
        if ($logger = $this->getLoggingConfiguration()->getOrmProfilingLogger()) {
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
        if ($logger = $this->getLoggingConfiguration()->getOrmProfilingLogger()) {
            $logger->startFlush();
            parent::flush($entity);
            $logger->stopFlush();
        } else {
            parent::flush($entity);
        }
    }

    /**
     * @return LoggingConfiguration
     */
    protected function getLoggingConfiguration()
    {
        return $this->getConfiguration();
    }
}
