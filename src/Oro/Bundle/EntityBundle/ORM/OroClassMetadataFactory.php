<?php

namespace Oro\Bundle\EntityBundle\ORM;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadataFactory;
use Oro\Bundle\EntityBundle\DataCollector\OrmLogger;

/**
 * Adds the following features to the Doctrine's ClassMetadataFactory:
 * * a possibility to mark the factory disconnected to be able to to memory usage optimization
 *   of the message queue consumer
 * * a memory cache for the result value of isTransient() method
 * * a possibility to profile ORM metadata related methods
 */
class OroClassMetadataFactory extends ClassMetadataFactory
{
    const ALL_METADATA_KEY = 'oro_entity.all_metadata';

    /** @var EntityManagerInterface|null */
    private $entityManager;

    /** @var bool[] */
    private $isTransientCache = [];

    /** @var OrmLogger */
    private $logger;

    /** @var bool */
    private $disconnected = false;

    /**
     * Indicates whether this metadata factory is in the disconnected state.
     *
     * @internal this method is intended to be used only to memory usage optimization of the message queue consumer
     *
     * @return bool
     */
    public function isDisconnected()
    {
        return $this->disconnected;
    }

    /**
     * Switches this metadata factory to the disconnected or connected state.
     *
     * @internal this method is intended to be used only to memory usage optimization of the message queue consumer
     */
    public function setDisconnected($disconnected)
    {
        $this->disconnected = $disconnected;
    }

    /**
     * Gets an instance of EntityManager connected to this this metadata factory.
     *
     * @internal this method is intended to be used only to memory usage optimization of the message queue consumer
     *
     * @return EntityManagerInterface|null
     */
    public function getEntityManager()
    {
        return $this->entityManager;
    }

    /**
     * {@inheritdoc}
     */
    public function setEntityManager(EntityManagerInterface $em)
    {
        if (!$this->disconnected) {
            parent::setEntityManager($em);
            $this->entityManager = $em;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getAllMetadata()
    {
        $logger = $this->getProfilingLogger();

        if (null !== $logger) {
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

        if (null !== $logger) {
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

        if (null !== $logger) {
            $logger->startGetMetadataFor();
        }

        $result = parent::getMetadataFor($className);

        if (null !== $logger) {
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

        if (null !== $logger) {
            $logger->startIsTransient();
        }

        if (isset($this->isTransientCache[$class])) {
            $result = $this->isTransientCache[$class];
        } else {
            $result = parent::isTransient($class);
            $this->isTransientCache[$class] = $result;
        }

        if (null !== $logger) {
            $logger->stopIsTransient();
        }

        return $result;
    }

    /**
     * Gets a profiling logger.
     *
     * @return OrmLogger|null
     */
    private function getProfilingLogger()
    {
        if (false === $this->logger) {
            return null;
        }

        if (null !== $this->logger) {
            return $this->logger;
        }

        if (null === $this->entityManager) {
            return null;
        }

        $config = $this->entityManager->getConfiguration();
        $this->logger = $config instanceof OrmConfiguration
            ? $config->getAttribute('OrmProfilingLogger', false)
            : false;

        if (false === $this->logger) {
            return null;
        }

        return $this->logger;
    }
}
