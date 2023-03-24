<?php

namespace Oro\Bundle\DashboardBundle\Form\Type;

use Oro\Bundle\FilterBundle\Form\Type\Filter\AbstractDateFilterType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Form type for Date Range widget:
 *  - Used together with {@see DependentDateWidgetDateRangeType};
 *  - Uses x-To-Date choices;
 *  - Provides an option "update_dependent_date_range_fields" which can be configured in order to automatically set
 *   values to the dependent Date Range fields.
 */
class CurrentDateWidgetDateRangeType extends AbstractType
{
    private const NAME = 'oro_type_current_date_widget_date_range';

    private TranslatorInterface $translator;

    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return self::NAME;
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return WidgetDateRangeType::class;
    }

    /**
     * {@inheritdoc}
     *
     * Example of converted option "update_dependent_date_range_fields" value:
     *  [
     *      AbstractDateFilterType::TYPE_ALL_TIME => [
     *          'select[name$="[dateRange2][type]] => AbstractDateFilterType::TYPE_NONE,
     *      ],
     *  ]
     */
    public function finishView(FormView $view, FormInterface $form, array $options)
    {
        parent::finishView($view, $form, $options);

        $dependentDateRangeFields = [];
        $updateDependentDateRangeFieldsOption = $form->getConfig()->getOption('update_dependent_date_range_fields');
        foreach ($updateDependentDateRangeFieldsOption as $currentDateRangeType => $dependentDateRangeData) {
            $currentDateRangeTypeValue = $this->getDateRangeTypeConstant($currentDateRangeType);
            foreach ($dependentDateRangeData as $fieldName => $defaultFieldValue) {
                $fieldSelector = 'select[name$="[' . $fieldName . '][type]"]';
                $dependentDateRangeFields[$currentDateRangeTypeValue][$fieldSelector] = $this->getDateRangeTypeConstant(
                    $defaultFieldValue
                );
            }
        }

        $view->vars['datetime_range_metadata']['autoUpdateBetweenWhenOneDate'] = false;
        $view->vars['datetime_range_metadata']['dependentDateRangeFields'] = $dependentDateRangeFields;
    }

    /**
     * {@inheritdoc}
     *
     * Example of "update_dependent_date_range_fields" option value:
     *  [
     *      'TYPE_ALL_TIME' => [
     *          'dateRange2' => 'TYPE_NONE',
     *      ],
     *  ]
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        parent::configureOptions($resolver);

        $resolver->setDefaults([
            'tooltip' => null,
            'value_types' => false,
            'update_dependent_date_range_fields' => [],
        ]);

        $resolver->setNormalizer('operator_choices', fn () => $this->getOperatorChoices());
    }

    private function getOperatorChoices(): array
    {
        return [
            $this->translator->trans('oro.dashboard.widget.filter.current_date_range.choices.today') =>
                AbstractDateFilterType::TYPE_TODAY,
            $this->translator->trans('oro.dashboard.widget.filter.current_date_range.choices.month_to_date') =>
                AbstractDateFilterType::TYPE_THIS_MONTH,
            $this->translator->trans('oro.dashboard.widget.filter.current_date_range.choices.quarter_to_date') =>
                AbstractDateFilterType::TYPE_THIS_QUARTER,
            $this->translator->trans('oro.dashboard.widget.filter.current_date_range.choices.year_to_date') =>
                AbstractDateFilterType::TYPE_THIS_YEAR,
            $this->translator->trans('oro.dashboard.widget.filter.current_date_range.choices.all_time') =>
                AbstractDateFilterType::TYPE_ALL_TIME,
            $this->translator->trans('oro.dashboard.widget.filter.current_date_range.choices.custom') =>
                AbstractDateFilterType::TYPE_BETWEEN,
        ];
    }

    private function getDateRangeTypeConstant(string $dateRangeType): int
    {
        $constantName = AbstractDateFilterType::class . '::' . $dateRangeType;
        if (defined($constantName)) {
            return constant($constantName);
        }

        return $dateRangeType;
    }
}
