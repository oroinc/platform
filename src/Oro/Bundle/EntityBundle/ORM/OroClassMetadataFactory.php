<?php

namespace Oro\Bundle\EntityBundle\ORM;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadataFactory;

use Oro\Bundle\EntityBundle\DataCollector\OrmLogger;

class OroClassMetadataFactory extends ClassMetadataFactory
{
    const ALL_METADATA_KEY = 'oro_entity.all_metadata';

    /** @var EntityManagerInterface|null */
    protected $entityManager;

    /** @var bool[] */
    protected $isTransientCache = [];

    /** @var OrmLogger */
    protected $logger;

    /**
     * {@inheritdoc}
     */
    public function setEntityManager(EntityManagerInterface $em)
    {
        parent::setEntityManager($em);

        $this->entityManager = $em;
    }

    /**
     * {@inheritdoc}
     */
    public function getAllMetadata()
    {
        $logger = $this->getProfilingLogger();

        if ($logger) {
            $logger->startGetAllMetadata();
        }

        $cacheDriver = $this->getCacheDriver();
        if ($cacheDriver) {
            $result = $cacheDriver->fetch(static::ALL_METADATA_KEY);
            if (false === $result) {
                $result = parent::getAllMetadata();
                $cacheDriver->save(static::ALL_METADATA_KEY, $result);
            } else {
                $reflectionService = $this->getReflectionService();
                foreach ($result as $metadata) {
                    $this->wakeupReflection($metadata, $reflectionService);
                }
            }
        } else {
            $result = parent::getAllMetadata();
        }

        if ($logger) {
            $logger->stopGetAllMetadata();
        }

        return $result;

    }

    /**
     * {@inheritdoc}
     */
    public function getMetadataFor($className)
    {
        $logger = $this->getProfilingLogger();

        if ($logger) {
            $logger->startGetMetadataFor();
        }

        $result = parent::getMetadataFor($className);

        if ($logger) {
            $logger->stopGetMetadataFor();
        }

        return $result;
    }

    /**
     * {@inheritDoc}
     */
    public function isTransient($class)
    {
        $logger = $this->getProfilingLogger();

        if ($logger) {
            $logger->startIsTransient();
        }

        if (array_key_exists($class, $this->isTransientCache)) {
            $result = $this->isTransientCache[$class];
        } else {
            $result = parent::isTransient($class);
            $this->isTransientCache[$class] = $result;
        }

        if ($logger) {
            $logger->stopIsTransient();
        }

        return $result;
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

        if (null === $this->entityManager) {
            return null;
        }

        $config = $this->entityManager->getConfiguration();

        $this->logger = $config instanceof OrmConfiguration
            ? $config->getAttribute('OrmProfilingLogger', false)
            : false;

        return $this->logger;
    }
}
