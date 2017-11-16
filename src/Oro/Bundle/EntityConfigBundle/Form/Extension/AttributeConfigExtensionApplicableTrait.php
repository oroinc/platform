<?php

namespace Oro\Bundle\EntityConfigBundle\Form\Extension;

use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;

trait AttributeConfigExtensionApplicableTrait
{
    /** @var ConfigProvider */
    protected $attributeConfigProvider;

    /**
     * @param FieldConfigModel $configModel
     * @return bool
     */
    protected function isApplicable(FieldConfigModel $configModel)
    {
        $className = $configModel->getEntity()->getClassName();
        $fieldName = $configModel->getFieldName();

        $hasAttributes = $this->attributeConfigProvider->getConfig($className)->is('has_attributes');
        $isAttribute = $this->attributeConfigProvider->getConfig($className, $fieldName)->is('is_attribute');

        return $hasAttributes && $isAttribute;
    }
}
