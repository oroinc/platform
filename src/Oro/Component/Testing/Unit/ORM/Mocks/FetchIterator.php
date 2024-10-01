<?php

namespace Oro\Component\Testing\Unit\ORM\Mocks;

/**
 * Iterates over \Doctrine\DBAL\Driver\Statement->fetch()
 */
class FetchIterator implements \Iterator
{
    /** @var StatementMock */
    protected $statement;

    /** @var mixed */
    protected $row;

    /** @var int */
    protected $key = -1;

    /**
     * FetchIterator constructor.
     */
    public function __construct(StatementMock $statement)
    {
        $this->statement = $statement;
    }

    #[\Override]
    public function current(): mixed
    {
        return $this->row;
    }

    #[\Override]
    public function next(): void
    {
        $this->row = $this->statement->fetch();
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
