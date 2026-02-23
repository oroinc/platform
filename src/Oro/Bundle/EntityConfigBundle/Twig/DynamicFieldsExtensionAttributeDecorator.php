<?php

namespace Oro\Bundle\EntityConfigBundle\Twig;

use Oro\Bundle\EntityConfigBundle\Config\AttributeConfigHelper;
use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Oro\Bundle\EntityExtendBundle\Twig\AbstractDynamicFieldsExtension;
use Psr\Container\ContainerInterface;
use Symfony\Component\Security\Acl\Util\ClassUtils;

/**
 * Decorates existing Twig functions to render attributes along with dynamic fields:
 *   - oro_get_dynamic_fields
 *   - oro_get_dynamic_field
 */
class DynamicFieldsExtensionAttributeDecorator extends AbstractDynamicFieldsExtension
{
    public function __construct(
        private readonly AbstractDynamicFieldsExtension $extension,
        ContainerInterface $container
    ) {
        parent::__construct($container);
    }

    #[\Override]
    public function getField($entity, FieldConfigModel $field)
    {
        return $this->extension->getField($entity, $field);
    }

    #[\Override]
    public function getFields($entity, $entityClass = null)
    {
        $fields = $this->extension->getFields($entity, $entityClass);
        if (null === $entityClass) {
            $entityClass = ClassUtils::getRealClass($entity);
        }

        $attributeHelper = $this->getAttributeHelper();

        return array_filter(
            $fields,
            function ($fieldName) use ($attributeHelper, $entityClass) {
                return !$attributeHelper->isFieldAttribute($entityClass, $fieldName);
            },
            ARRAY_FILTER_USE_KEY
        );
    }

    #[\Override]
    public static function getSubscribedServices(): array
    {
        return [
            AttributeConfigHelper::class
        ];
    }

    private function getAttributeHelper(): AttributeConfigHelper
    {
        return $this->container->get(AttributeConfigHelper::class);
    }
}
