<?php

namespace Oro\Bundle\FilterBundle\Form\Type\Filter;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Translation\TranslatorInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;

use Oro\Bundle\FilterBundle\Filter\FilterUtility;

class NumberFilterType extends AbstractType implements NumberFilterTypeInterface
{
    const NAME = 'oro_type_number_filter';

    /**
     * @var TranslatorInterface
     */
    protected $translator;

    /**
     * @param TranslatorInterface $translator
     */
    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    /**
     * {@inheritDoc}
     */
    public function getName()
    {
        return self::NAME;
    }

    /**
     * {@inheritDoc}
     */
    public function getParent()
    {
        return FilterType::NAME;
    }

    /**
     * {@inheritDoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $operatorChoices = array(
            self::TYPE_EQUAL              => $this->translator->trans('oro.filter.form.label_type_equal'),
            self::TYPE_NOT_EQUAL          => $this->translator->trans('oro.filter.form.label_type_not_equal'),
            self::TYPE_GREATER_EQUAL      => $this->translator->trans('oro.filter.form.label_type_greater_equal'),
            self::TYPE_GREATER_THAN       => $this->translator->trans('oro.filter.form.label_type_greater_than'),
            self::TYPE_LESS_EQUAL         => $this->translator->trans('oro.filter.form.label_type_less_equal'),
            self::TYPE_LESS_THAN          => $this->translator->trans('oro.filter.form.label_type_less_than'),
            FilterUtility::TYPE_EMPTY     => $this->translator->trans('oro.filter.form.label_type_empty'),
            FilterUtility::TYPE_NOT_EMPTY => $this->translator->trans('oro.filter.form.label_type_not_empty'),
        );

        $resolver->setDefaults(
            array(
                'field_type'        => 'number',
                'operator_choices'  => $operatorChoices,
                'data_type'         => self::DATA_INTEGER,
                'formatter_options' => array()
            )
        );
    }

    /**
     * @param FormView      $view
     * @param FormInterface $form
     * @param array         $options
     */
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $dataType = self::DATA_INTEGER;
        if (isset($options['data_type'])) {
            $dataType = $options['data_type'];
        }

        $formatterOptions = array();

        switch ($dataType) {
            case self::PERCENT:
                $formatterOptions['decimals'] = 2;
                $formatterOptions['grouping'] = false;
                $formatterOptions['percent'] = true;
                break;
            case self::DATA_DECIMAL:
                $formatterOptions['decimals'] = 2;
                $formatterOptions['grouping'] = true;
                break;
            case self::DATA_INTEGER:
            default:
                $formatterOptions['decimals'] = 0;
                $formatterOptions['grouping'] = false;
        }

        $formatter = new \NumberFormatter(\Locale::getDefault(), \NumberFormatter::DECIMAL);

        $formatterOptions['orderSeparator'] = $formatterOptions['grouping']
            ? $formatter->getSymbol(\NumberFormatter::GROUPING_SEPARATOR_SYMBOL)
            : '';

        $formatterOptions['decimalSeparator'] = $formatter->getSymbol(\NumberFormatter::DECIMAL_SEPARATOR_SYMBOL);

        $view->vars['formatter_options'] = array_merge($formatterOptions, $options['formatter_options']);
    }
}
