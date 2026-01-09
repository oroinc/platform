<?php

namespace Oro\Bundle\ImportExportBundle\Processor;

/**
 * Defines the contract for processors that are aware of the entity name being processed.
 *
 * This interface combines {@see ProcessorInterface} and {@see EntityNameAwareInterface}, indicating
 * that implementations are processors that can receive and utilize the entity name during processing.
 */
interface EntityNameAwareProcessor extends ProcessorInterface, EntityNameAwareInterface
{
}
