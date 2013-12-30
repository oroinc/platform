<?php

namespace Oro\Bundle\TestFrameworkBundle\Test\Doctrine\ORM\Mocks;

/**
 * This class is a clone of namespace Doctrine\Tests\Mocks\StatementMock that is excluded from doctrine
 * package since v2.4.
 */
class StatementMock implements \IteratorAggregate, \Doctrine\DBAL\Driver\Statement
{
    public function bindValue($param, $value, $type = null)
    {

    }

    public function bindParam($column, &$variable, $type = null, $length = null)
    {

    }

    public function errorCode()
    {

    }

    public function errorInfo()
    {

    }

    public function execute($params = null)
    {

    }

    public function rowCount()
    {

    }

    public function closeCursor()
    {

    }

    public function columnCount()
    {

    }

    public function setFetchMode($fetchStyle, $arg2 = null, $arg3 = null)
    {

    }

    public function fetch($fetchStyle = null)
    {

    }

    public function fetchAll($fetchStyle = null)
    {

    }

    public function fetchColumn($columnIndex = 0)
    {

    }

    public function getIterator()
    {

    }
}
