<?php

namespace Oro\Bundle\MigrationBundle\Migration;

use Doctrine\DBAL\Connection;

class SqlMigrationQuery implements MigrationQuery
{
    /**
     * @var string|string[]
     */
    protected $sql;

    /**
     * @param string|string[] $sql
     * @throws \InvalidArgumentException if $sql is empty
     */
    public function __construct($sql)
    {
        if (empty($sql)) {
            throw new \InvalidArgumentException('The SQL query must not be empty.');
        }

        if (is_array($sql) && count($sql) === 1) {
            $this->sql = array_pop($sql);
        } else {
            $this->sql = $sql;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getDescription()
    {
        return $this->sql;
    }

    /**
     * {@inheritdoc}
     */
    public function execute(Connection $connection)
    {
        foreach ((array)$this->sql as $sql) {
            $connection->executeQuery($sql);
        }
    }
}
