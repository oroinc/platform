<?php

namespace Oro\Bundle\EntityConfigBundle\Layout\DataProvider;

use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider as AttributeProvider;

class AttributeConfigProvider
{
    /** @var AttributeProvider */
    protected $attributeProvider;

    /**
     * @param AttributeProvider $attributeProvider
     */
    public function __construct(AttributeProvider $attributeProvider)
    {
        $this->attributeProvider = $attributeProvider;
    }

    public function getConfig($entity, $fieldName)
    {
        return $this->attributeProvider->getConfig($entity, $fieldName);
    }
}
