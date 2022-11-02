<?php

namespace Oro\Bundle\ImportExportBundle\Serializer\Normalizer;

use Symfony\Component\Serializer\Normalizer\ContextAwareDenormalizerInterface;

/**
 * Converts value of the supported scalar field types from the current scalar representation
 * to the field type representation
 */
interface ScalarFieldDenormalizerInterface extends ContextAwareDenormalizerInterface
{
    public function addFieldToIgnore(string $className, string $fieldName);

    public function addConvertTypeMappings(string $doctrineType, string $toType);
}
