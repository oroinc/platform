<?php

namespace Oro\Bundle\ImportExportBundle\Processor;

use Akeneo\Bundle\BatchBundle\Item\ItemProcessorInterface;
use Akeneo\Bundle\BatchBundle\Step\StepExecutionAwareInterface;

interface StepExecutionAwareProcessor extends ItemProcessorInterface, StepExecutionAwareInterface
{
}
