<?php

namespace Oro\Bundle\EntityBundle\ORM;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadataFactory;

use Oro\Bundle\EntityBundle\DataCollector\OrmLogger;

class OroClassMetadataFactory extends ClassMetadataFactory
{
    /** @var EntityManagerInterface|null */
    protected $entityManager;

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
        if ($logger = $this->getProfilingLogger()) {
            $logger->startGetAllMetadata();
            $result = parent::getAllMetadata();
            $logger->stopGetAllMetadata();

            return $result;
        } else {
            return parent::getAllMetadata();
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getMetadataFor($className)
    {
        if ($logger = $this->getProfilingLogger()) {
            $logger->startGetMetadataFor();
            $result = parent::getMetadataFor($className);
            $logger->stopGetMetadataFor();

            return $result;
        } else {
            return parent::getMetadataFor($className);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function isTransient($class)
    {
        if ($logger = $this->getProfilingLogger()) {
            $logger->startIsTransient();
            $result = parent::isTransient($class);
            $logger->stopIsTransient();

            return $result;
        } else {
            return parent::isTransient($class);
        }
    }

    /**
     * Gets a profiling logger.
     *
     * @return OrmLogger|null
     */
    protected function getProfilingLogger()
    {
        if (null === $this->entityManager) {
            return null;
        }

        $config = $this->entityManager->getConfiguration();

        return $config instanceof OrmConfiguration
            ? $config->getAttribute('OrmProfilingLogger')
            : null;
    }
}
