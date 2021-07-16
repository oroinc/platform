<?php

namespace Oro\Bundle\ImportExportBundle\Serializer\Normalizer;

/**
 * Converts value of the supported scalar field types from the current scalar representation
 * to the field type representation
 */
interface ScalarFieldDenormalizerInterface extends DenormalizerInterface
{
    public function addFieldToIgnore(string $className, string $fieldName);

    public function addConvertTypeMappings(string $doctrineType, string $toType);
}
