<?php

namespace Oro\Bundle\CurrencyBundle\Form\Type;

use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Validator\Constraints\NotBlank;

use Oro\Bundle\CurrencyBundle\Entity\MultiCurrency;

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
                    'attr' => ['data-scale' => $this->roundingService->getPrecision()]
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
}
