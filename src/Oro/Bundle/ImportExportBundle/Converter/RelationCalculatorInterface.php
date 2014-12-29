<?php

namespace Oro\Bundle\ImportExportBundle\Converter;

interface RelationCalculatorInterface
{
    /**
     * @param string $entityName
     * @param string $fieldName
     * @return int
     */
    public function getMaxRelatedEntities($entityName, $fieldName);
}
