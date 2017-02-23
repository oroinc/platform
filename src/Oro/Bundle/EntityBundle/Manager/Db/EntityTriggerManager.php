<?php

namespace Oro\Bundle\EntityBundle\Manager\Db;

use Oro\Bundle\EntityBundle\ORM\DatabaseDriverInterface;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;

class EntityTriggerManager
{
    /**
     * @var string
     */
    private $entityClass;

    /**
     * @var DatabaseDriverInterface|EntityTriggerDriverInterface[]
     */
    private $drivers;

    /**
     * @var \Doctrine\DBAL\Connection
     */
    private $connection;

    /**
     * @var DoctrineHelper
     */
    private $doctrineHelper;

    /**
     * @param DoctrineHelper $doctrineHelper
     * @param string         $entityClass
     */
    public function __construct(
        DoctrineHelper $doctrineHelper,
        $entityClass
    ) {
        $this->doctrineHelper = $doctrineHelper;
        $this->entityClass    = $entityClass;
    }

    /**
     * @param DatabaseDriverInterface $driver
     */
    public function addDriver(DatabaseDriverInterface $driver)
    {
        $this->drivers[$driver->getName()] = $driver;
    }

    /**
     * {@inheritdoc}
     */
    public function disable()
    {
        return $this->getDriver()->disable();
    }

    /**
     * {@inheritdoc}
     */
    public function enable()
    {
        return $this->getDriver()->enable();
    }

    /**
     * @return EntityTriggerDriverInterface|DatabaseDriverInterface
     */
    private function getDriver()
    {
        $this->connection = $this->doctrineHelper->getEntityManagerForClass($this->entityClass)
            ->getConnection();

        $platform = $this->connection->getParams()['driver'];

        if (!isset($this->drivers[$platform])) {
            throw new \RuntimeException('Driver not installed: ' . $platform);
        }

        $driver = $this->drivers[$platform];

        // make sure drivers use proper entityClass from caller
        $driver->setEntityClass($this->entityClass);

        return $driver;
    }
}
