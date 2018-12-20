<?php

namespace Oro\Bundle\FilterBundle\Form\Type\Filter;

use Oro\Bundle\FilterBundle\Filter\FilterUtility;
use Oro\Bundle\LocaleBundle\Model\LocaleSettings;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * Form type for number filters
 */
class NumberFilterType extends AbstractType implements NumberFilterTypeInterface
{
    const NAME = 'oro_type_number_filter';
    const ARRAY_SEPARATOR = ',';

    /**
     * @var TranslatorInterface
     */
    protected $translator;

    /**
     * @var LocaleSettings
     */
    private $localeSettings;

    /**
     * @param TranslatorInterface $translator
     * @param LocaleSettings $localeSettings
     */
    public function __construct(TranslatorInterface $translator, LocaleSettings $localeSettings)
    {
        $this->translator = $translator;
        $this->localeSettings = $localeSettings;
    }

    /**
     * {@inheritDoc}
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

    /**
     * {@inheritDoc}
     */
    public function getParent()
    {
        return FilterType::class;
    }

    /**
     * {@inheritDoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        // Change value field type to text if IN or NOT IN is used as condition
        $builder->addEventListener(
            FormEvents::PRE_SUBMIT,
            function (FormEvent $event) {
                $form = $event->getForm();
                $data = $event->getData();
                if (!empty($data['type']) && in_array($data['type'], self::ARRAY_TYPES)) {
                    $options = $form->get('value')->getConfig()->getOptions();
                    $form->remove('value');
                    $form->add('value', TextType::class, [
                        'label' => $options['label'], 'required' => $options['required']
                    ]);
                }
            }
        );
    }

    /**
     * {@inheritDoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $operatorChoices = [
            $this->translator->trans('oro.filter.form.label_type_equal') => self::TYPE_EQUAL,
            $this->translator->trans('oro.filter.form.label_type_not_equal') => self::TYPE_NOT_EQUAL,
            $this->translator->trans('oro.filter.form.label_type_greater_equal') => self::TYPE_GREATER_EQUAL,
            $this->translator->trans('oro.filter.form.label_type_greater_than') => self::TYPE_GREATER_THAN,
            $this->translator->trans('oro.filter.form.label_type_less_equal') => self::TYPE_LESS_EQUAL,
            $this->translator->trans('oro.filter.form.label_type_less_than') => self::TYPE_LESS_THAN,
            $this->translator->trans('oro.filter.form.label_type_in') => self::TYPE_IN,
            $this->translator->trans('oro.filter.form.label_type_not_in') => self::TYPE_NOT_IN,
            $this->translator->trans('oro.filter.form.label_type_empty') => FilterUtility::TYPE_EMPTY,
            $this->translator->trans('oro.filter.form.label_type_not_empty') => FilterUtility::TYPE_NOT_EMPTY,
        ];

        $resolver->setDefaults(
            array(
                'field_type'        => NumberType::class,
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

        $formatter = new \NumberFormatter($this->localeSettings->getLocale(), \NumberFormatter::DECIMAL);

        $formatterOptions['orderSeparator'] = $formatterOptions['grouping']
            ? $formatter->getSymbol(\NumberFormatter::GROUPING_SEPARATOR_SYMBOL)
            : '';

        $formatterOptions['decimalSeparator'] = $formatter->getSymbol(\NumberFormatter::DECIMAL_SEPARATOR_SYMBOL);

        $view->vars['formatter_options'] = array_merge($formatterOptions, $options['formatter_options']);
        $view->vars['array_separator'] = self::ARRAY_SEPARATOR;
        $view->vars['array_operators'] = self::ARRAY_TYPES;
        $view->vars['data_type'] = $dataType;
    }
}
