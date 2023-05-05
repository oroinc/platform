<?php

namespace Oro\Bundle\EntityBundle\ORM;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadataFactory;
use Doctrine\Persistence\Mapping\ClassMetadata as ClassMetadataInterface;
use Doctrine\Persistence\Mapping\MappingException;
use Doctrine\Persistence\Mapping\ReflectionService;
use Oro\Bundle\EntityBundle\DataCollector\OrmLogger;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendReflectionErrorHandler;

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

        $cacheDriver = $this->getCache();
        if ($cacheDriver) {
            $cacheItem = $cacheDriver->getItem(static::ALL_METADATA_KEY);
            if (!$cacheItem->isHit()) {
                $result = parent::getAllMetadata();
                $cacheDriver->save($cacheItem->set($result));
            } else {
                $result = $cacheItem->get();
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

        try {
            $result = parent::getMetadataFor($className);
        } catch (\ReflectionException $e) {
            if (ExtendReflectionErrorHandler::isSupported($e)) {
                throw ExtendReflectionErrorHandler::createException($className, $e);
            } else {
                throw $e;
            }
        }

        if (null !== $logger) {
            $logger->stopGetMetadataFor();
        }

        return $result;
    }

    protected function initializeReflection(ClassMetadataInterface $class, ReflectionService $reflService)
    {
        $className = $class->getName();
        if (!class_exists($className)) {
            throw MappingException::nonExistingClass($className);
        }
        parent::initializeReflection($class, $reflService);
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

    public function clearCache(): void
    {
        $this->getCache()?->clear();
    }
}
