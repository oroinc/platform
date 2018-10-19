<?php

namespace Oro\Bundle\CurrencyBundle\Form\Type;

use Oro\Bundle\CurrencyBundle\Entity\MultiCurrency;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\CurrencyBundle\Form\DataTransformer\MoneyValueTransformer;
use Oro\Bundle\FormBundle\Form\Type\OroMoneyType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * Builds multi currency select with value and currency inputs, handles constraints assigning depending on default value
 */
class MultiCurrencyType extends PriceType
{
    const NAME = 'oro_multicurrency';

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
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $isRequired = $this->isRequired($options);

        $builder
            ->add(
                'value',
                NumberType::class,
                [
                    'required' => $isRequired,
                    'scale' => Price::MAX_VALUE_SCALE,
                    'constraints' => $options['value_constraints']
                ]
            )
            ->add(
                'currency',
                CurrencySelectionType::class,
                [
                    'additional_currencies' => $options['additional_currencies'],
                    'currencies_list' => $options['currencies_list'],
                    'full_currency_list' => $options['full_currency_list'],
                    'compact' => false,
                    'required' => $isRequired,
                    'placeholder' => false
                ]
            );

        $builder->get('value')->addModelTransformer(new MoneyValueTransformer());

        $builder->addEventListener(
            FormEvents::PRE_SET_DATA,
            function (FormEvent $event) {
                /** @var MultiCurrency $initialData */
                $initialData = $event->getData();
                $options = ['required' => false];

                if ($initialData && null !== $initialData->getBaseCurrencyValue()) {
                    $options['constraints'] = [
                        new NotBlank()
                    ];
                }

                $event->getForm()->add('baseCurrencyValue', OroMoneyType::class, $options);
            }
        );
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        parent::configureOptions($resolver);
        $resolver->setDefaults(['value_constraints' => [],]);
    }

    /**
     * {@inheritdoc}
     */
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $view->vars['currencyRates'] = [];
    }
}
