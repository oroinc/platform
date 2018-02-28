<?php

namespace Oro\Bundle\CurrencyBundle\Form\Type;

use Oro\Bundle\CurrencyBundle\Entity\MultiCurrency;
use Oro\Bundle\CurrencyBundle\Form\DataTransformer\MoneyValueTransformer;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

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
                'number',
                [
                    'required' => $isRequired,
                    'scale' => $this->roundingService->getPrecision(),
                    'rounding_mode' => $this->roundingService->getRoundType(),
                    'attr' => ['data-scale' => $this->roundingService->getPrecision()],
                    'constraints' => $options['value_constraints']
                ]
            )
            ->add(
                'currency',
                CurrencySelectionType::NAME,
                [
                    'additional_currencies' => $options['additional_currencies'],
                    'currencies_list' => $options['currencies_list'],
                    'full_currency_list' => $options['full_currency_list'],
                    'compact' => false,
                    'required' => $isRequired,
                    'empty_value' => false
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

                $event->getForm()->add('baseCurrencyValue', 'oro_money', $options);
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
