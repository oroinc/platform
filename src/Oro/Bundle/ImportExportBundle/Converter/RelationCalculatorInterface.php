<?php

namespace Oro\Bundle\ImportExportBundle\Converter;

/**
 * Defines the contract for calculating the maximum number of related entities.
 *
 * Implementations determine the maximum count of related entities for a given field
 * of an entity type. This information is used during export to determine how many
 * columns are needed to represent one-to-many or many-to-many relationships in
 * tabular export formats.
 */
interface RelationCalculatorInterface
{
    /**
     * @param string $entityName
     * @param string $fieldName
     * @return int
     */
    public function getMaxRelatedEntities($entityName, $fieldName);
}
