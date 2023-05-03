<?php

namespace Oro\Bundle\ApiBundle\Processor\Subresource\Shared;

use Oro\Bundle\ApiBundle\Processor\Subresource\SubresourceContext;
use Oro\Bundle\ApiBundle\Request\DataType;

/**
 * Loads nested association data using the EntitySerializer component.
 * As returned data is already normalized, the "normalize_data" group will be skipped.
 */
class LoadNestedAssociation extends LoadCustomAssociation
{
    /**
     * {@inheritdoc}
     */
    protected function isSupportedAssociation(string $dataType): bool
    {
        return DataType::isNestedAssociation($dataType);
    }

    /**
     * {@inheritdoc}
     */
    protected function loadAssociationData(
        SubresourceContext $context,
        string $associationName,
        string $dataType
    ): void {
        $this->saveAssociationDataToContext(
            $context,
            $this->loadData($context, $associationName, false)
        );
    }
}
