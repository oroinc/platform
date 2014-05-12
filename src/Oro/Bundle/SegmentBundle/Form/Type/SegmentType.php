<?php

namespace Oro\Bundle\SegmentBundle\Form\Type;

use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

use Oro\Bundle\QueryDesignerBundle\Form\Type\AbstractQueryDesignerType;

class SegmentType extends AbstractQueryDesignerType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('name', 'text', ['required' => true])
            ->add('entity', 'oro_segment_entity_choice', ['required' => true])
            ->add(
                'type',
                'entity',
                [
                    'class'       => 'OroSegmentBundle:SegmentType',
                    'property'    => 'label',
                    'required'    => true,
                    'empty_value' => 'oro.segment.form.choose_segment_type'
                ]
            )
            ->add('description', 'textarea', ['required' => false]);

        parent::buildForm($builder, $options);
    }

    /**
     * Gets the default options for this type.
     *
     * @return array
     */
    public function getDefaultOptions()
    {
        return [
            'column_column_choice_type' => 'hidden',
            'filter_column_choice_type' => 'oro_entity_field_select'
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $options = array_merge(
            $this->getDefaultOptions(),
            [
                'data_class'         => 'Oro\Bundle\SegmentBundle\Entity\Segment',
                'intention'          => 'segment',
                'cascade_validation' => true
            ]
        );

        $resolver->setDefaults($options);
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'oro_segment';
    }
}
