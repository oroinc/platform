<?php

namespace Oro\Bundle\ImportExportBundle\Serializer\Normalizer;

/**
 * Converts value of the supported scalar field types from the current scalar representation
 * to the field type representation
 */
interface ScalarFieldDenormalizerInterface extends DenormalizerInterface
{
    /**
     * @param string $className
     * @param string $fieldName
     */
    public function addFieldToIgnore(string $className, string $fieldName);

    /**
     * @param string $doctrineType
     * @param string $toType
     */
    public function addConvertTypeMappings(string $doctrineType, string $toType);
}
