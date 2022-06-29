<?php

namespace Oro\Bundle\ImportExportBundle\Reader;

use Oro\Bundle\ImportExportBundle\Exception\LogicException;

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
            $context = $this->getContext();
            $context->incrementReadOffset();
            $context->incrementReadCount();
        }

        return $result;
    }

    /**
     * Separate from function read, to prevent the paging occurred before last item be passed and processed
     * that will confuse the order of events like Events::AFTER_ENTITY_PAGE_LOADED and Events::AFTER_NORMALIZE_ENTITY
     */
    public function next(): void
    {
        if ($this->sourceIterator->valid()) {
            $this->sourceIterator->next();
        }
    }

    /**
     * Setter for iterator
     *
     * @param \Iterator $sourceIterator
     */
    public function setSourceIterator(\Iterator $sourceIterator = null)
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
