<?php

namespace Oro\Bundle\ApiBundle\Form\Type;

use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Form\EventListener\CompoundObjectListener;
use Oro\Bundle\ApiBundle\Form\FormHelper;
use Oro\Bundle\ApiBundle\Metadata\EntityMetadata;
use Oro\Bundle\ApiBundle\Metadata\PropertyMetadata;
use Oro\Bundle\ApiBundle\Request\DataType;
use Oro\Bundle\ApiBundle\Util\ConfigUtil;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * The form type for an object that properties are built based of API metadata
 * and contain only properties classified as fields and associations that should be represented as a field.
 * Usually this form type is used if an association should be represented as a field in API.
 * @see \Oro\Bundle\ApiBundle\Request\DataType::isAssociationAsField
 */
class CompoundObjectType extends AbstractType
{
    private FormHelper $formHelper;

    public function __construct(FormHelper $formHelper)
    {
        $this->formHelper = $formHelper;
    }

    /**
     * {@inheritDoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var EntityMetadata $metadata */
        $metadata = $options['metadata'];
        /** @var EntityDefinitionConfig $config */
        $config = $options['config'];
        $inheritData = $options['inherit_data'];
        $readOnlyChildren = (false === $options['children_mapped']);

        $fields = $metadata->getFields();
        foreach ($fields as $name => $field) {
            $this->addFormField($builder, $config, $name, $field, $inheritData, $readOnlyChildren);
        }
        $associations = $metadata->getAssociations();
        foreach ($associations as $name => $association) {
            if (DataType::isAssociationAsField($association->getDataType())) {
                $this->addFormField($builder, $config, $name, $association, $inheritData, $readOnlyChildren);
            }
        }

        $builder->addEventSubscriber(new CompoundObjectListener());
    }

    /**
     * {@inheritDoc}
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver
            ->setRequired(['metadata', 'config'])
            ->setDefault('children_mapped', null)
            ->setAllowedTypes('metadata', [EntityMetadata::class])
            ->setAllowedTypes('config', [EntityDefinitionConfig::class])
            ->setAllowedTypes('children_mapped', ['bool', 'null']);
    }

    private function addFormField(
        FormBuilderInterface $formBuilder,
        EntityDefinitionConfig $config,
        string $fieldName,
        PropertyMetadata $fieldMetadata,
        bool $inheritData,
        bool $readOnly
    ): void {
        $fieldConfig = $config->getField($fieldName);
        $options = ['required' => false];
        if ($readOnly) {
            $options['mapped'] = false;
        }
        if ($inheritData) {
            $propertyPath = $fieldConfig->getPropertyPath();
            if (ConfigUtil::IGNORE_PROPERTY_PATH === $propertyPath) {
                $options['mapped'] = false;
            } else {
                $options['property_path'] = $fieldConfig->getPropertyPath();
            }
        }

        $this->formHelper->addFormField(
            $formBuilder,
            $fieldName,
            $fieldConfig,
            $fieldMetadata,
            $options,
            true
        );
    }
}
