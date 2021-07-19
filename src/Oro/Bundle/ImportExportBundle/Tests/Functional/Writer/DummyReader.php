<?php

namespace Oro\Bundle\ImportExportBundle\Tests\Functional\Writer;

use Oro\Bundle\BatchBundle\Entity\StepExecution;
use Oro\Bundle\ImportExportBundle\Reader\ReaderInterface;

class DummyReader implements ReaderInterface
{
    /** @var iterable */
    private $source;

    public function __construct(\Iterator $source)
    {
        $this->source = $source;
        $this->source->rewind();
    }

    public function read()
    {
        $result = null;
        if ($this->source->valid()) {
            $result = $this->source->current();
            $this->source->next();
        }

        return $result;
    }

    public function setStepExecution(StepExecution $stepExecution)
    {
    }
}
