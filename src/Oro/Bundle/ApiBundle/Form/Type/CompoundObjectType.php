<?php

namespace Oro\Bundle\ApiBundle\Form\Type;

use Oro\Bundle\ApiBundle\Form\EventListener\CompoundObjectListener;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Form\FormUtil;
use Oro\Bundle\ApiBundle\Metadata\EntityMetadata;
use Oro\Bundle\ApiBundle\Request\DataType;

class CompoundObjectType extends AbstractType
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
                FormUtil::getFormFieldOptions($field, $fieldConfig)
            );
        }
        $associations = $metadata->getAssociations();
        foreach ($associations as $name => $association) {
            if (DataType::isAssociationAsField($association->getDataType())) {
                $fieldConfig = $config->getField($name);
                $builder->add(
                    $name,
                    $fieldConfig->getFormType(),
                    FormUtil::getFormFieldOptions($association, $fieldConfig)
                );
            }
        }

        $builder
            ->addEventSubscriber(new CompoundObjectListener());
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
        return 'oro_api_compound_object';
    }
}
