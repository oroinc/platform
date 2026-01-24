<?php

namespace Oro\Bundle\ImportExportBundle\Processor;

use Oro\Bundle\ImportExportBundle\Context\ContextAwareInterface;

/**
 * Defines the contract for processors that are aware of the import/export context.
 *
 * This interface combines {@see ProcessorInterface} and {@see ContextAwareInterface}, indicating that implementations
 * are processors that can receive and utilize the current import/export context during processing.
 */
interface ContextAwareProcessor extends ProcessorInterface, ContextAwareInterface
{
}
