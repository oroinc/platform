<?php

namespace Oro\Bundle\EntityConfigBundle\Config;

use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider as AttributeProvider;

class AttributeConfigHelper
{
    const CODE_IS_ATTRIBUTE = 'is_attribute';
    const CODE_HAS_ATTRIBUTES = 'has_attributes';

    /**
     * @var AttributeProvider
     */
    private $attributeProvider;

    /**
     * @param AttributeProvider $attributeProvider
     */
    public function __construct(AttributeProvider $attributeProvider)
    {
        $this->attributeProvider= $attributeProvider;
    }

    /**
     * @param string $entityClass
     * @param string $fieldName
     * @return bool
     */
    public function isFieldAttribute($entityClass, $fieldName)
    {
        if (!$this->attributeProvider->hasConfig($entityClass, $fieldName)) {
            return false;
        }

        return $this->attributeProvider
            ->getConfig($entityClass, $fieldName)
            ->is(self::CODE_IS_ATTRIBUTE);
    }

    /**
     * @param string $entityClass
     * @return bool
     */
    public function isEntityWithAttributes($entityClass)
    {
        if (!$this->attributeProvider->hasConfig($entityClass)) {
            return false;
        }

        return $this->attributeProvider
            ->getConfig($entityClass)
            ->is(self::CODE_HAS_ATTRIBUTES);
    }
}
