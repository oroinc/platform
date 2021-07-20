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
    /** @var AbstractDynamicFieldsExtension */
    private $extension;

    public function __construct(AbstractDynamicFieldsExtension $extension, ContainerInterface $container)
    {
        parent::__construct($container);
        $this->extension = $extension;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedServices()
    {
        return [
            'oro_entity_config.config.attributes_config_helper' => AttributeConfigHelper::class,
        ];
    }

    /**
     * @return AttributeConfigHelper
     */
    private function getAttributeHelper()
    {
        return $this->container->get('oro_entity_config.config.attributes_config_helper');
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

        $attributeHelper = $this->getAttributeHelper();

        return array_filter(
            $fields,
            function ($fieldName) use ($attributeHelper, $entityClass) {
                return !$attributeHelper->isFieldAttribute($entityClass, $fieldName);
            },
            ARRAY_FILTER_USE_KEY
        );
    }
}
