<?php

namespace Oro\Component\Testing\Unit\ORM\Mocks;

/**
 * This class is a clone of namespace Doctrine\Tests\Mocks\StatementMock that is excluded from doctrine
 * package since v2.4.
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
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

    public function execute($params = null): bool
    {
        return true;
    }

    public function rowCount(): int
    {
        return 1;
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

    public function fetch($fetchMode = null, $cursorOrientation = \PDO::FETCH_ORI_NEXT, $cursorOffset = 0)
    {
    }

    public function fetchAll($fetchMode = null, $fetchArgument = null, $ctorArgs = null)
    {
    }

    public function fetchColumn($columnIndex = 0)
    {
    }

    public function getIterator(): \Traversable
    {
    }
}
