<?php

namespace Oro\Component\TestUtils\ORM\Mocks;

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
     *
     * @param StatementMock $statement
     */
    public function __construct(StatementMock $statement)
    {
        $this->statement = $statement;
    }

    /**
     * {@inheritdoc}
     */
    public function current()
    {
        return $this->row;
    }

    /**
     * {@inheritdoc}
     */
    public function next()
    {
        $this->row = $this->statement->fetch();
        $this->key++;
    }

    /**
     * {@inheritdoc}
     */
    public function key()
    {
        return $this->key;
    }

    /**
     * {@inheritdoc}
     */
    public function valid()
    {
        return false !== $this->row;
    }

    /**
     * {@inheritdoc}
     */
    public function rewind()
    {
        $this->next();
    }
}
