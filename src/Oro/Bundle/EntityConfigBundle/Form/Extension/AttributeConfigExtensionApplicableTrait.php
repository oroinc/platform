<?php

namespace Oro\Bundle\EntityConfigBundle\Form\Extension;

use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;

/**
 * Provides logic to determine if an attribute configuration form extension should be applied.
 *
 * This trait encapsulates the logic for checking whether a field configuration model represents an attribute
 * in an entity that supports attributes. It is used by form extensions to conditionally apply attribute-specific
 * form modifications only when appropriate.
 */
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
