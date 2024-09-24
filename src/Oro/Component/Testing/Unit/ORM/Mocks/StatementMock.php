<?php

namespace Oro\Component\Testing\Unit\ORM\Mocks;

/**
 * This class is a clone of namespace Doctrine\Tests\Mocks\StatementMock that is excluded from doctrine
 * package since v2.4.
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class StatementMock implements \IteratorAggregate, \Doctrine\DBAL\Driver\Statement
{
    #[\Override]
    public function bindValue($param, $value, $type = null)
    {
    }

    #[\Override]
    public function bindParam($column, &$variable, $type = null, $length = null)
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

    #[\Override]
    public function execute($params = null): bool
    {
        return true;
    }

    #[\Override]
    public function rowCount(): int
    {
        return 1;
    }

    #[\Override]
    public function closeCursor()
    {
    }

    #[\Override]
    public function columnCount()
    {
    }

    #[\Override]
    public function setFetchMode($fetchStyle, $arg2 = null, $arg3 = null)
    {
    }

    #[\Override]
    public function fetch($fetchMode = null, $cursorOrientation = \PDO::FETCH_ORI_NEXT, $cursorOffset = 0)
    {
    }

    #[\Override]
    public function fetchAll($fetchMode = null, $fetchArgument = null, $ctorArgs = null)
    {
    }

    #[\Override]
    public function fetchColumn($columnIndex = 0)
    {
    }

    #[\Override]
    public function getIterator(): \Traversable
    {
    }
}
