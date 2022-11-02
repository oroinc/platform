<?php

namespace Oro\Bundle\QueryDesignerBundle\Form\Type;

use Oro\Bundle\EntityBundle\Form\Type\EntityFieldSelectType;
use Oro\Bundle\QueryDesignerBundle\Model\DateGrouping;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * The form type to manage the grouping by date section in the query designer.
 */
class DateGroupingType extends AbstractType
{
    const NAME = 'oro_query_designer_date_grouping';
    const FIELD_NAME_ID = 'fieldName';
    const USE_SKIP_EMPTY_PERIODS_FILTER_ID = 'useSkipEmptyPeriodsFilter';
    const USE_DATE_GROUPING_FILTER = 'useDateGroupFilter';
    const DATE_GROUPING_NAME = 'date_grouping';

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(
                static::FIELD_NAME_ID,
                DateFieldChoiceType::class,
                [
                    'required'            => true,
                    'label'               => 'oro.query_designer.form.date_grouping.date_field.label',
                    'page_component_name' => 'date-grouping-field-choice',
                ]
            )
            ->add(
                static::USE_SKIP_EMPTY_PERIODS_FILTER_ID,
                CheckboxType::class,
                [
                    'required' => false,
                    'label' => 'oro.query_designer.form.date_grouping.use_skip_empty_periods.label',
                ]
            )
            ->add(
                static::USE_DATE_GROUPING_FILTER,
                CheckboxType::class,
                [
                    'required' => false,
                    'label' => 'oro.query_designer.form.date_grouping.use_date_grouping_filter.label',
                ]
            );
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'entity' => null,
                'data_class' => DateGrouping::class,
                'csrf_token_id' => 'query_designer_date_grouping',
                'column_choice_type' => EntityFieldSelectType::class,
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
