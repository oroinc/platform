<?php

namespace Oro\Bundle\FilterBundle\Form\Type\Filter;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Translation\TranslatorInterface;

abstract class AbstractDateFilterType extends AbstractType
{
    const TYPE_BETWEEN     = 1;
    const TYPE_NOT_BETWEEN = 2;
    const TYPE_MORE_THAN   = 3;
    const TYPE_LESS_THAN   = 4;

    const PART_VALUE   = 'value';
    const PART_DOW     = 'dayofweek';
    const PART_WEEK    = 'week';
    const PART_DAY     = 'day';
    const PART_MONTH   = 'month';
    const PART_QUARTER = 'quarter';
    const PART_DOY     = 'dayofyear';
    const PART_YEAR    = 'year';

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
     * @return array
     */
    public static function getTypeValues()
    {
        return [
            'between'    => self::TYPE_BETWEEN,
            'notBetween' => self::TYPE_NOT_BETWEEN,
            'moreThan'   => self::TYPE_MORE_THAN,
            'lessThan'   => self::TYPE_LESS_THAN
        ];
    }

    public function getOperatorChoices()
    {
        return [
            self::TYPE_BETWEEN     => $this->translator->trans('oro.filter.form.label_date_type_between'),
            self::TYPE_NOT_BETWEEN => $this->translator->trans('oro.filter.form.label_date_type_not_between'),
            self::TYPE_MORE_THAN   => $this->translator->trans('oro.filter.form.label_date_type_more_than'),
            self::TYPE_LESS_THAN   => $this->translator->trans('oro.filter.form.label_date_type_less_than'),
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $t = $this->translator;
        $resolver->setDefaults(
            [
                'date_parts' => [
                    self::PART_VALUE   => $t->trans('oro.filter.form.label_date_part.value'),
                    self::PART_DOW     => $t->trans('oro.filter.form.label_date_part.dayofweek'),
                    self::PART_WEEK    => $t->trans('oro.filter.form.label_date_part.week'),
                    self::PART_DAY     => $t->trans('oro.filter.form.label_date_part.day'),
                    self::PART_MONTH   => $t->trans('oro.filter.form.label_date_part.month'),
                    self::PART_QUARTER => $t->trans('oro.filter.form.label_date_part.quarter'),
                    self::PART_DOY     => $t->trans('oro.filter.form.label_date_part.dayofyear'),
                    self::PART_YEAR    => $t->trans('oro.filter.form.label_date_part.year'),
                ],
            ]
        );
    }

    /**
     * @param FormView      $view
     * @param FormInterface $form
     * @param array         $options
     */
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $widgetOptions                = ['firstDay' => 0];
        $view->vars['widget_options'] = array_merge($widgetOptions, $options['widget_options']);
        $view->vars['date_parts']     = $options['date_parts'];
    }
}
