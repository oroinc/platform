<?php

namespace Oro\Bundle\DashboardBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\ChoiceList\SimpleChoiceList;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Translation\TranslatorInterface;

use Oro\Bundle\FilterBundle\Form\Type\Filter\DateRangeFilterType;
use Oro\Bundle\FilterBundle\Form\Type\Filter\AbstractDateFilterType;

class WidgetDateRangeType extends AbstractType
{
    const NAME = 'oro_type_widget_date_range';

    /** @var TranslatorInterface */
    protected $translator;

    /**
     * @param TranslatorInterface $translator
     */
    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return self::NAME;
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return DateRangeFilterType::NAME;
    }

    /**
     * {@inheritdoc}
     */
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        parent::buildView($view, $form, $options);

        /** @var SimpleChoiceList $choices */
        $choices = $form->get('type')->getConfig()->getOption('choice_list');

        $view->vars['datetime_range_metadata'] = [
            'name'       => $view->vars['full_name'] . '[type]',
            'label'      => $view->vars['label'],
            'choices'    => $choices->getRemainingViews(),
            'typeValues' => $view->vars['type_values'],
            'dateParts'  => $view->vars['date_parts'],
            'externalWidgetOptions'  => array_merge(
                $view->vars['widget_options'],
                ['dateVars' => $view->vars['date_vars']]
            ),
            'templateSelector'       => '#date-filter-template-wo-actions',
            'criteriaValueSelectors' => [
                'type'      => 'select',
                'date_type' => 'select[name!=date_part]',
                'date_part' => 'select[name=date_part]',
                'value'     => [
                    'start' => 'input[name=\"' . $view->vars['full_name'] . '[value][start]\"]',
                    'end'   => 'input[name=\"' . $view->vars['full_name'] . '[value][end]\"]'
                ]
            ]
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        parent::setDefaultOptions($resolver);

        $resolver->setDefaults(
            [
                'required'         => false,
                'field_type'       => WidgetDateRangeValueType::NAME,
                'operator_choices' => $this->getOperatorChoices(),
                'widget_options'   => [
                    'showTime'       => false,
                    'showTimepicker' => false
                ]
            ]
        );
    }

    /**
     * @return array
     */
    protected function getOperatorChoices()
    {
        return [
            AbstractDateFilterType::TYPE_BETWEEN
                => $this->translator->trans('oro.filter.form.label_date_type_between'),
            AbstractDateFilterType::TYPE_MORE_THAN
                => $this->translator->trans('oro.filter.form.label_date_type_more_than'),
            AbstractDateFilterType::TYPE_LESS_THAN
                => $this->translator->trans('oro.filter.form.label_date_type_less_than')
        ];
    }
}
