<?php

namespace Oro\Bundle\CurrencyBundle\Form\Type;

use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\CurrencyBundle\Form\DataTransformer\PriceTransformer;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Represents price input and sets required options
 */
class PriceType extends AbstractType
{
    const NAME = 'oro_currency_price';
    const OPTIONAL_VALIDATION_GROUP = 'Optional';

    /**
     * @var string
     */
    protected $dataClass;

    /**
     * @param string $dataClass
     */
    public function setDataClass($dataClass)
    {
        $this->dataClass = $dataClass;
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $isRequiredPrice = $this->isRequired($options);

        if (empty($options['hide_currency'])) {
            $currencyType = CurrencySelectionType::class;
            $currencyOptions = [
                'additional_currencies' => $options['additional_currencies'],
                'currencies_list' => $options['currencies_list'],
                'full_currency_list' => $options['full_currency_list'],
                'compact' => $options['compact'],
                'required' => $isRequiredPrice,
                'placeholder' => $options['currency_empty_value'],
            ];
        } else {
            $currencyType = HiddenType::class;
            $currencyOptions = [
                'data' => $options['default_currency']
            ];
        }

        $builder
            ->add(
                'value',
                NumberType::class,
                [
                    'required' => $isRequiredPrice,
                    'scale' => Price::MAX_VALUE_SCALE,
                    'attr' => [
                        'data-match-price-on-null' =>  $options['match_price_on_null'] ? 1 : 0
                    ]
                ]
            )
            ->add('currency', $currencyType, $currencyOptions);

        $builder->addViewTransformer(new PriceTransformer());
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => $this->dataClass,
            'hide_currency' => false,
            'additional_currencies' => null,
            'currencies_list' => null,
            'default_currency' => null,
            'full_currency_list' => false,
            'currency_empty_value' => 'oro.currency.currency.form.choose',
            'compact' => false,
            'validation_groups'=> ['Default'],
            'match_price_on_null' => true
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function finishView(FormView $view, FormInterface $form, array $options)
    {
        $view->vars['hide_currency'] = $options['hide_currency'];
    }

    /**
     * @return string
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
     * @param array $options
     * @return bool
     */
    protected function isRequired(array $options)
    {
        return array_key_exists('validation_groups', $options)
            && is_array($options['validation_groups'])
            && !in_array(self::OPTIONAL_VALIDATION_GROUP, $options['validation_groups'], true);
    }
}
