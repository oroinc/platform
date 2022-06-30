<?php

namespace Oro\Bundle\ImportExportBundle\Reader;

use Iterator;
use Oro\Bundle\ImportExportBundle\Exception\LogicException;

/**
 * An item reader that help to pass item in current cursor, move to next or pagination from a query source.
 */
abstract class IteratorBasedReader extends AbstractReader
{
    private Iterator $sourceIterator;

    private bool $rewound = false;

    private bool $goNext = false;

    /**
     * {@inheritdoc}
     */
    public function read()
    {
        if (null === $this->getSourceIterator()) {
            throw new LogicException('Reader must be configured with source');
        }
        if (!$this->rewound) {
            $this->sourceIterator->rewind();
            $this->rewound = true;
        }

        $result = null;
        if ($this->sourceIterator->valid()) {
            $result  = $this->sourceIterator->current();

            $this->goNext = true;
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function next(): mixed
    {
        if ($this->goNext) {
            $context = $this->getContext();
            $context->incrementReadOffset();
            $context->incrementReadCount();
            $this->sourceIterator->next();

            $this->goNext = false;
        }

        return null;
    }

    /**
     * Setter for iterator
     *
     * @param Iterator $sourceIterator
     */
    public function setSourceIterator(Iterator $sourceIterator = null)
    {
        $this->sourceIterator = $sourceIterator;
        $this->rewound        = false;
    }

    /**
     * Getter for iterator
     *
     * @return Iterator|null
     */
    public function getSourceIterator()
    {
        return $this->sourceIterator;
    }
}
