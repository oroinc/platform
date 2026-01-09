<?php

namespace Oro\Component\Testing\Unit\ORM\Mocks;

use PHPUnit\Framework\MockObject\MockObject;

/**
 * Iterates over \Doctrine\DBAL\Driver\Result->fetchAssociative()
 */
class FetchIterator implements \Iterator
{
    /** @var ResultMock|MockObject */
    protected $result;

    /** @var mixed */
    protected $row;

    /** @var int */
    protected $key = -1;

    /**
     * FetchIterator constructor.
     */
    public function __construct(ResultMock|MockObject $result)
    {
        $this->result = $result;
    }

    #[\Override]
    public function current(): mixed
    {
        return $this->row;
    }

    #[\Override]
    public function next(): void
    {
        $this->row = $this->result->fetchAssociative();
        $this->key++;
    }

    #[\Override]
    public function key(): int
    {
        return $this->key;
    }

    #[\Override]
    public function valid(): bool
    {
        return false !== $this->row;
    }

    #[\Override]
    public function rewind(): void
    {
        $this->next();
    }
}
