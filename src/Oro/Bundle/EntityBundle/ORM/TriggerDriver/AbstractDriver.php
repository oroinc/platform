<?php

namespace Oro\Bundle\EntityBundle\ORM\TriggerDriver;

use Oro\Bundle\EntityBundle\Manager\Db\EntityTriggerDriverInterface;
use Oro\Bundle\EntityBundle\ORM\DatabaseDriverInterface;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;

abstract class AbstractDriver implements DatabaseDriverInterface, EntityTriggerDriverInterface
{
    /**
     * @var DoctrineHelper
     */
    protected $doctrineHelper;

    /**
     * @var string
     */
    protected $entityClass;

    /**
     * @var \Doctrine\DBAL\Connection
     */
    protected $connection;

    /**
     * @var string
     */
    protected $tableName;

    /**
     * @param DoctrineHelper $doctrineHelper
     */
    public function __construct(DoctrineHelper $doctrineHelper)
    {
        $this->doctrineHelper = $doctrineHelper;
    }
    /**
     * {@inheritdoc}
     */
    public function disable()
    {
        $this->init();
        $this->connection->exec(sprintf($this->getSqlDisable(), $this->tableName));
    }

    /**
     * {@inheritdoc}
     */
    public function enable()
    {
        $this->init();
        $this->connection->exec(sprintf($this->getSqlEnable(), $this->tableName));
    }

    /**
     * {@inheritdoc}
     */
    public function setEntityClass($entityClass)
    {
        $this->entityClass = $entityClass;
    }

    /**
     * @return string
     */
    abstract protected function getSqlDisable();

    /**
     * @return string
     */
    abstract protected function getSqlEnable();

    private function init()
    {
        $this->connection = $this->doctrineHelper->getEntityManagerForClass($this->entityClass)
            ->getConnection();

        $this->tableName = $this->doctrineHelper->getEntityManagerForClass($this->entityClass)
            ->getClassMetadata($this->entityClass)
            ->getTableName();
    }
}
