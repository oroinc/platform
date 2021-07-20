<?php

namespace Oro\Bundle\ImportExportBundle\Processor;

use Oro\Bundle\BatchBundle\Item\ItemProcessorInterface;
use Oro\Bundle\BatchBundle\Step\StepExecutionAwareInterface;

/**
 * Batch job item processor extended with StepExecutionAwareInterface.
 */
interface StepExecutionAwareProcessor extends ItemProcessorInterface, StepExecutionAwareInterface
{
}
