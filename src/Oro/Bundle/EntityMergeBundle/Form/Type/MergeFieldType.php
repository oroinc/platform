<?php

namespace Oro\Bundle\EntityMergeBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

use Oro\Bundle\EntityMergeBundle\Metadata\FieldMetadata;

class MergeFieldType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        /** @var FieldMetadata $metadata */
        $metadata = $options['metadata'];

        $builder->add(
            'sourceEntity',
            'entity',
            array(
                'class' => 'OroCRM\\Bundle\\AccountBundle\\Entity\\Account', // @todo Pass class dynamically
                'choices' => $options['entities'],
                'multiple' => false,
                'expanded' => true,
            )
        );

        $builder->add(
            'mode',
            'hidden'
        );
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
                'data_class' => 'Oro\\Bundle\\EntityMergeBundle\\Data\\FieldData'
            )
        );

        $resolver->setAllowedTypes(
            array(
                'metadata' => 'Oro\\Bundle\\EntityMergeBundle\\Metadata\\FieldMetadata',
                'entities' => 'array',
            )
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'oro_entity_merge_field';
    }
}
