<?php

namespace Oro\Bundle\EntityMergeBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

use Oro\Bundle\EntityMergeBundle\Metadata\EntityMetadata;

class MergeType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        /** @var EntityMetadata $metadata */
        $metadata = $options['metadata'];

        $builder->add(
            'masterEntity',
            'entity',
            array(
                'label' => 'Master Entity', // @todo Translate this string
                'class' => 'OroCRM\\Bundle\\AccountBundle\\Entity\\Account', // @todo Pass class dynamically
                'choices' => $options['entities'],
                'multiple' => false,
                'expanded' => true
            )
        );
        $builder->add('fields', 'form');
        $fields = $builder->get('fields');

        foreach ($metadata->getFieldsMetadata() as $fieldMetadata) {
            $fields->add(
                $fieldMetadata->getFieldName(),
                'oro_entity_merge_field',
                array(
                    'metadata' => $fieldMetadata,
                    'entities' => $options['entities'],
                    'property_path' => sprintf('[%s]', $fieldMetadata->getFieldName()),
                )
            );
        }
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setRequired(
            array(
                'metadata',
                'entities',
            )
        );

        $resolver->setDefaults(
            array(
                'data_class' => 'Oro\\Bundle\\EntityMergeBundle\\Data\\EntityData'
            )
        );

        $resolver->setAllowedTypes(
            array(
                'metadata' => 'Oro\\Bundle\\EntityMergeBundle\\Metadata\\EntityMetadata',
                'entities' => 'array',
            )
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'oro_entity_merge';
    }
}
