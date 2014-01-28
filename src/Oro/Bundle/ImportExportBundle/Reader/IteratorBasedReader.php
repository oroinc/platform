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
            $this->sourceIterator->next();
        }

        return $result;
    }

    /**
     * Setter for iterator
     *
     * @param \Iterator $sourceIterator
     */
    protected function setSourceIterator(\Iterator $sourceIterator)
    {
        $this->sourceIterator = $sourceIterator;
        $this->rewound        = false;
    }

    /**
     * Getter for iterator
     *
     * @return \Iterator|null
     */
    protected function getSourceIterator()
    {
        return $this->sourceIterator;
    }
}
