<?php

namespace Oro\Bundle\ImportExportBundle\Reader;

use Oro\Bundle\BatchBundle\Item\ItemReaderInterface;
use Oro\Bundle\BatchBundle\Step\StepExecutionAwareInterface;

/**
 * Batch jobs reader interface extended with StepExecutionAwareInterface.
 */
interface ReaderInterface extends ItemReaderInterface, StepExecutionAwareInterface
{
}
