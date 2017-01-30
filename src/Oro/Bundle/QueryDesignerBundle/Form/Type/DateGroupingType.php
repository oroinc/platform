<?php

namespace Oro\Bundle\QueryDesignerBundle\Form\Type;

use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Form\AbstractType;

class DateGroupingType extends AbstractType
{
    const NAME = 'oro_query_designer_date_grouping';
    const FIELD_NAME_ID = 'fieldName';
    const USE_SKIP_EMPTY_PERIODS_FILTER_ID = 'useSkipEmptyPeriodsFilter';
    const NOT_NULLABLE_FIELD = 'notNullableField';

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(static::FIELD_NAME_ID, 'oro_date_field_choice', ['required' => false])
            ->add(static::NOT_NULLABLE_FIELD, 'oro_field_choice', ['required' => false])
            ->add(static::USE_SKIP_EMPTY_PERIODS_FILTER_ID, CheckboxType::class);
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(
            [
                'entity' => null,
                'data_class' => 'Oro\Bundle\QueryDesignerBundle\Model\DateGrouping',
                'intention' => 'query_designer_date_grouping',
                'column_choice_type' => 'oro_entity_field_select',
            ]
        );
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
        return self::NAME;
    }
}
