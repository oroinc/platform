<?php

namespace Oro\Bundle\EntityExtendBundle\Twig;

use Oro\Bundle\EntityConfigBundle\Config\AttributeConfigHelper;
use Oro\Bundle\EntityConfigBundle\Config\ConfigInterface;
use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Symfony\Component\Security\Acl\Util\ClassUtils;

class DynamicFieldsExtensionAttributeDecorator extends DynamicFieldsExtension
{
    /**
     * @var DynamicFieldsExtension
     */
    private $extension;

    /**
     * @var AttributeConfigHelper
     */
    private $attributeHelper;

    /**
     * @param DynamicFieldsExtension $extension
     * @param AttributeConfigHelper $attributeHelper
     */
    public function __construct(DynamicFieldsExtension $extension, AttributeConfigHelper $attributeHelper)
    {
        $this->extension = $extension;
        $this->attributeHelper = $attributeHelper;
    }

    /**
     * {@inheritdoc}
     */
    public function getFunctions()
    {
        return $this->extension->getFunctions();
    }

    /**
     * {@inheritdoc}
     */
    public function getField($entity, FieldConfigModel $field)
    {
        return $this->extension->getField($entity, $field);
    }

    /**
     * @param object      $entity
     * @param null|string $entityClass
     * @return array
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

    /**
     * {@inheritdoc}
     */
    public function filterFields(ConfigInterface $config)
    {
        return $this->extension->filterFields($config);
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return $this->extension->getName();
    }
}
