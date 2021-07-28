<?php

namespace Oro\Bundle\ImportExportBundle\Processor;

/**
 * Interface for import/export processors aware of the name of the entity they are responsible for processing.
 */
interface EntityNameAwareInterface
{
    /**
     * Set entity name that this processor is used for
     *
     * @param string $entityName
     */
    public function setEntityName(string $entityName): void;
}
