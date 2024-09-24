<?php

namespace Oro\Component\Testing\Unit\ORM\Mocks;

/**
 * This class is a clone of namespace Doctrine\Tests\Mocks\DriverConnectionMock that is excluded from doctrine
 * package since v2.4.
 */
class DriverConnectionMock implements \Doctrine\DBAL\Driver\Connection
{
    #[\Override]
    public function prepare($prepareString)
    {
    }

    #[\Override]
    public function query()
    {
        return new StatementMock();
    }

    #[\Override]
    public function quote($input, $type = \PDO::PARAM_STR)
    {
    }

    #[\Override]
    public function exec($statement)
    {
    }

    #[\Override]
    public function lastInsertId($name = null)
    {
    }

    #[\Override]
    public function beginTransaction()
    {
    }

    #[\Override]
    public function commit()
    {
    }

    #[\Override]
    public function rollBack()
    {
    }

    #[\Override]
    public function errorCode()
    {
    }

    #[\Override]
    public function errorInfo()
    {
    }
}
