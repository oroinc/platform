<?php

namespace Oro\Bundle\TestFrameworkBundle\Test\Doctrine\ORM\Mocks;

/**
 * This class is a clone of namespace Doctrine\Tests\Mocks\DriverConnectionMock that is excluded from doctrine
 * package since v2.4.
 */
class DriverConnectionMock implements \Doctrine\DBAL\Driver\Connection
{
    public function prepare($prepareString)
    {

    }

    public function query()
    {
        return new StatementMock;
    }

    public function quote($input, $type = \PDO::PARAM_STR)
    {

    }

    public function exec($statement)
    {

    }

    public function lastInsertId($name = null)
    {

    }

    public function beginTransaction()
    {

    }

    public function commit()
    {

    }

    public function rollBack()
    {

    }

    public function errorCode()
    {

    }

    public function errorInfo()
    {

    }
}
