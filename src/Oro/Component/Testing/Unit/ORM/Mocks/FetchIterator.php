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

    /**
     * {@inheritdoc}
     */
    public function current(): mixed
    {
        return $this->row;
    }

    /**
     * {@inheritdoc}
     */
    public function next(): void
    {
        $this->row = $this->statement->fetch();
        $this->key++;
    }

    /**
     * {@inheritdoc}
     */
    public function key(): int
    {
        return $this->key;
    }

    /**
     * {@inheritdoc}
     */
    public function valid(): bool
    {
        return false !== $this->row;
    }

    /**
     * {@inheritdoc}
     */
    public function rewind(): void
    {
        $this->next();
    }
}
