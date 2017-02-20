<?php

namespace Oro\Bundle\EntityBundle\ORM\Driver;

use Oro\Bundle\EntityBundle\Manager\Db\EntityTriggerDriverInterface;
use Oro\Bundle\EntityBundle\ORM\DatabaseDriverInterface;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;

abstract class AbstractDriver implements DatabaseDriverInterface, EntityTriggerDriverInterface
{
    /**
     * @var string
     */
    protected $sql_disable = '';

    /**
     * @var string
     */
    protected $sql_enable = '';

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
        $this->connection->exec(sprintf($this->sql_disable, $this->tableName));
    }

    /**
     * {@inheritdoc}
     */
    public function enable()
    {
        $this->init();
        $this->connection->exec(sprintf($this->sql_enable, $this->tableName));
    }

    /**
     * {@inheritdoc}
     */
    public function setEntityClass($entityClass)
    {
        $this->entityClass = $entityClass;
    }

    private function init()
    {
        $this->connection = $this->doctrineHelper->getEntityManagerForClass($this->entityClass)
            ->getConnection();

        $this->tableName = $this->doctrineHelper->getEntityManagerForClass($this->entityClass)
            ->getClassMetadata($this->entityClass)
            ->getTableName();
    }
}
