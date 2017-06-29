<?php

namespace Oro\Bundle\ApiBundle\Processor\Subresource\Shared;

use Oro\Bundle\ApiBundle\Processor\Subresource\SubresourceContext;
use Oro\Bundle\ApiBundle\Request\DataType;

/**
 * Loads extended association data using the EntitySerializer component.
 * As returned data is already normalized, the "normalize_data" group will be skipped.
 */
class LoadExtendedAssociation extends LoadCustomAssociation
{
    /**
     * {@inheritdoc}
     */
    protected function isSupportedAssociation($dataType)
    {
        return DataType::isExtendedAssociation($dataType);
    }

    /**
     * {@inheritdoc}
     */
    protected function loadAssociationData(SubresourceContext $context, $associationName, $dataType)
    {
        list($associationType, ) = DataType::parseExtendedAssociation($dataType);
        $this->saveAssociationDataToContext(
            $context,
            $this->loadData($context, $associationName, $this->isCollection($associationType))
        );
    }
}
