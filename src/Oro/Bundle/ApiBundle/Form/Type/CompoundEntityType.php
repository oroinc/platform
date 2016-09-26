<?php

namespace Oro\Bundle\ApiBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionFieldConfig;
use Oro\Bundle\ApiBundle\Metadata\EntityMetadata;
use Oro\Bundle\ApiBundle\Request\DataType;
use Oro\Bundle\ApiBundle\Util\ConfigUtil;

class CompoundEntityType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        /** @var EntityMetadata $metadata */
        $metadata = $options['metadata'];
        /** @var EntityDefinitionConfig $config */
        $config = $options['config'];

        $fields = $metadata->getFields();
        foreach ($fields as $name => $field) {
            $fieldConfig = $config->getField($name);
            $builder->add(
                $name,
                $fieldConfig->getFormType(),
                $this->getFormFieldOptions($fieldConfig)
            );
        }
        $associations = $metadata->getAssociations();
        foreach ($associations as $name => $association) {
            if (DataType::isAssociationAsField($association->getDataType())) {
                $fieldConfig = $config->getField($name);
                $builder->add(
                    $name,
                    $fieldConfig->getFormType(),
                    $this->getFormFieldOptions($fieldConfig)
                );
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver
            ->setRequired(['metadata', 'config'])
            ->setAllowedTypes('metadata', ['Oro\Bundle\ApiBundle\Metadata\EntityMetadata'])
            ->setAllowedTypes('config', ['Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig']);
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return $this->getBlockPrefix();
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'oro_api_compound_entity';
    }

    /**
     * @param EntityDefinitionFieldConfig $fieldConfig
     *
     * @return array
     */
    protected function getFormFieldOptions(EntityDefinitionFieldConfig $fieldConfig)
    {
        $options = $fieldConfig->getFormOptions();
        if (null === $options) {
            $options = [];
        }
        $propertyPath = $fieldConfig->getPropertyPath();
        if ($propertyPath) {
            if (ConfigUtil::IGNORE_PROPERTY_PATH === $propertyPath) {
                $options['mapped'] = false;
            } else {
                $options['property_path'] = $propertyPath;
            }
        }

        return $options;
    }
}
