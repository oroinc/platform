<?php

namespace Oro\Bundle\ImportExportBundle\Reader;

use Oro\Bundle\BatchBundle\Item\ItemReaderInterface;
use Oro\Bundle\BatchBundle\Step\StepExecutionAwareInterface;

/**
 * Batch jobs reader interface extended with StepExecutionAwareInterface.
 */
interface ReaderInterface extends ItemReaderInterface, StepExecutionAwareInterface
{
    /**
     * Used to move cursor to next record for iterators, decouple from function read in order to prevent pagination
     * occurred before item be read and processed. You can keep it empty if there is no such problem for your iterator.
     */
    public function next(): mixed;
}
