<?php

namespace Oro\Bundle\ImportExportBundle\Reader;

use Oro\Bundle\ImportExportBundle\Exception\LogicException;

class IteratorBasedReader extends AbstractReader
{
    /**
     * @var \Iterator
     */
    protected $sourceIterator;

    /**
     * @var bool
     */
    private $rewound = false;

    /**
     * {@inheritdoc}
     */
    public function read()
    {
        if (null === $this->sourceIterator) {
            throw new LogicException('Reader must be configured with source');
        }
        if (!$this->rewound) {
            $this->sourceIterator->rewind();
            $this->rewound = true;
        }

        $result = null;
        if ($this->sourceIterator->valid()) {
            $result = $this->sourceIterator->current();
            $context = $this->getContext();
            $context->incrementReadOffset();
            $context->incrementReadCount();
            $this->sourceIterator->next();
        }

        return $result;
    }
}
