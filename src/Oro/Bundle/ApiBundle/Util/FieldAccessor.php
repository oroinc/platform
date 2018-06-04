<?php

namespace Oro\Bundle\ApiBundle\Util;

use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Component\EntitySerializer\EntityConfig;
use Oro\Component\EntitySerializer\FieldAccessor as BaseFieldAccessor;

/**
 * The field accessor that adds mandatory fields for SELECT clause.
 */
class FieldAccessor extends BaseFieldAccessor
{
    /** @var MandatoryFieldProviderRegistry */
    private $mandatoryFieldProvider;

    /** @var RequestType|null */
    private $requestType;

    /**
     * @param MandatoryFieldProviderRegistry $mandatoryFieldProvider
     */
    public function setMandatoryFieldProvider(MandatoryFieldProviderRegistry $mandatoryFieldProvider)
    {
        $this->mandatoryFieldProvider = $mandatoryFieldProvider;
    }

    /**
     * @param RequestType|null $requestType
     */
    public function setRequestType(RequestType $requestType = null)
    {
        $this->requestType = $requestType;
    }

    /**
     * {@inheritdoc}
     */
    public function getFieldsToSelect($entityClass, EntityConfig $config, $withAssociations = false)
    {
        $fields = parent::getFieldsToSelect($entityClass, $config, $withAssociations);
        if (null !== $this->requestType) {
            $mandatoryFields = $this->mandatoryFieldProvider->getMandatoryFields($entityClass, $this->requestType);
            if (!empty($mandatoryFields)) {
                $fields = \array_unique(\array_merge($fields, $mandatoryFields));
            }
        }

        return $fields;
    }
}
