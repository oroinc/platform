<?php

namespace Oro\Bundle\SegmentBundle\Form\Type;

use Oro\Bundle\EntityBundle\Form\Type\EntityFieldSelectType;
use Oro\Bundle\QueryDesignerBundle\Form\Type\AbstractQueryDesignerType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Segment form type
 * Used for creating segments, extends abstract query designer
 */
class SegmentType extends AbstractQueryDesignerType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('name', TextType::class, ['required' => true])
            ->add('entity', SegmentEntityChoiceType::class, ['required' => true])
            ->add(
                'type',
                EntityType::class,
                [
                    'class'       => 'OroSegmentBundle:SegmentType',
                    'choice_label'    => 'label',
                    'required'    => true,
                    'placeholder' => 'oro.segment.form.choose_segment_type',
                    'tooltip'     => 'oro.segment.type.tooltip_text'
                ]
            )
            ->add('recordsLimit', IntegerType::class, ['required' => false])
            ->add('description', TextareaType::class, ['required' => false]);

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
            'column_column_field_choice_options' => [
                'exclude_fields' => ['relationType'],
            ],
            'column_column_choice_type' => HiddenType::class,
            'filter_column_choice_type' => EntityFieldSelectType::class
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        parent::configureOptions($resolver);

        $options = array_merge(
            $this->getDefaultOptions(),
            [
                'data_class'         => 'Oro\Bundle\SegmentBundle\Entity\Segment',
                'csrf_token_id'      => 'segment',
                'query_type'         => 'segment',
            ]
        );

        $resolver->setDefaults($options);
    }

    /**
     * {@inheritdoc}
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
        return 'oro_segment';
    }
}
