<?php

namespace Oro\Component\Testing\Unit\ORM\Mocks;

use Doctrine\DBAL\Driver\Result;
use Doctrine\DBAL\Driver\Statement;
use Doctrine\DBAL\ParameterType;

/**
 * This class is a clone of namespace Doctrine\Tests\Mocks\DriverConnectionMock that is excluded from doctrine
 * package since v2.4.
 */
class DriverConnectionMock implements \Doctrine\DBAL\Driver\Connection
{
    #[\Override]
    public function prepare($sql): Statement
    {
        return new StatementMock();
    }

    #[\Override]
    public function query(string $sql): Result
    {
        return new ResultMock();
    }

    #[\Override]
    public function quote($value, $type = ParameterType::STRING)
    {
    }

    #[\Override]
    public function exec($sql): int
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
}
