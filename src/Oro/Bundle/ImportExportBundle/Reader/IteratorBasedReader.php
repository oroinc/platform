<?php

namespace Oro\Bundle\ImportExportBundle\Reader;

use Oro\Bundle\ImportExportBundle\Exception\LogicException;

/**
 * Provides common functionality for readers that use iterators as data sources.
 *
 * This base class manages iterator lifecycle (rewinding, iteration) and provides the read operation
 * by consuming items from the source iterator. Subclasses must implement the `getSourceIterator` method
 * to provide the actual data source iterator.
 */
abstract class IteratorBasedReader extends AbstractReader
{
    /**
     * @var \Iterator
     */
    private $sourceIterator;

    /**
     * @var bool
     */
    private $rewound = false;

    #[\Override]
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
            $context = $this->getContext();
            $context->incrementReadOffset();
            $context->incrementReadCount();
            $this->sourceIterator->next();
        }

        return $result;
    }

    /**
     * Setter for iterator
     */
    public function setSourceIterator(?\Iterator $sourceIterator = null)
    {
        $this->sourceIterator = $sourceIterator;
        $this->rewound        = false;
    }

    /**
     * Getter for iterator
     *
     * @return \Iterator|null
     */
    public function getSourceIterator()
    {
        return $this->sourceIterator;
    }
}
