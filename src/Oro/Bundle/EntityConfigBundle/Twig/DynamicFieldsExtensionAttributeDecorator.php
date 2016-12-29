<?php

namespace Oro\Bundle\EntityConfigBundle\Twig;

use Oro\Bundle\EntityConfigBundle\Config\AttributeConfigHelper;
use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Oro\Bundle\EntityExtendBundle\Twig\AbstractDynamicFieldsExtension;

use Symfony\Component\Security\Acl\Util\ClassUtils;

class DynamicFieldsExtensionAttributeDecorator extends AbstractDynamicFieldsExtension
{
    /**
     * @var AbstractDynamicFieldsExtension
     */
    private $extension;

    /**
     * @var AttributeConfigHelper
     */
    private $attributeHelper;

    /**
     * @param AbstractDynamicFieldsExtension $extension
     * @param AttributeConfigHelper $attributeHelper
     */
    public function __construct(AbstractDynamicFieldsExtension $extension, AttributeConfigHelper $attributeHelper)
    {
        $this->extension = $extension;
        $this->attributeHelper = $attributeHelper;
    }

    /**
     * {@inheritdoc}
     */
    public function getField($entity, FieldConfigModel $field)
    {
        return $this->extension->getField($entity, $field);
    }

    /**
     * {@inheritdoc}
     */
    public function getFields($entity, $entityClass = null)
    {
        $fields = $this->extension->getFields($entity, $entityClass);
        if (null === $entityClass) {
            $entityClass = ClassUtils::getRealClass($entity);
        }

        return array_filter(
            $fields,
            function ($fieldName) use ($entityClass) {
                return !$this->attributeHelper->isFieldAttribute($entityClass, $fieldName);
            },
            ARRAY_FILTER_USE_KEY
        );
    }
}
