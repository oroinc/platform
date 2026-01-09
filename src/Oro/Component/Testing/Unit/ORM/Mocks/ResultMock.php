<?php

namespace Oro\Component\Testing\Unit\ORM\Mocks;

use Doctrine\DBAL\Driver\Result;

/**
 * Mock implementation of Doctrine's database result interface for testing.
 */
class ResultMock implements \IteratorAggregate, Result
{
    public function fetchNumeric()
    {
    }

    public function fetchAssociative()
    {
    }

    public function fetchOne()
    {
    }

    public function fetchAllNumeric(): array
    {
        return [];
    }

    public function fetchAllAssociative(): array
    {
        return [];
    }

    public function fetchFirstColumn(): array
    {
        return [];
    }

    public function rowCount(): int
    {
        return 1;
    }

    public function columnCount(): int
    {
        return 1;
    }

    public function free(): void
    {
    }

    public function getIterator(): \Traversable
    {
        return new \ArrayIterator([]);
    }
}
