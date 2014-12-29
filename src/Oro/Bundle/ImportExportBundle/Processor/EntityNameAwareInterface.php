<?php

namespace Oro\Bundle\ImportExportBundle\Processor;

interface EntityNameAwareInterface
{
    /**
     * Set entity name that this processor is used for
     *
     * @param string $entityName
     */
    public function setEntityName($entityName);
}
