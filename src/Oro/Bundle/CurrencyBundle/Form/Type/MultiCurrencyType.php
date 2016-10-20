<?php

namespace Oro\Bundle\CurrencyBundle\Form\Type;

use Oro\Bundle\CurrencyBundle\Entity\MultiCurrency;
use Oro\Bundle\CurrencyBundle\Entity\MultiCurrencyHolderInterface;
use Oro\Bundle\CurrencyBundle\Form\DataTransformer\MultiCurrencyTransformer;

use Oro\Bundle\FormBundle\Utils\FormUtils;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;

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
                    'empty_value' => $options['currency_empty_value']
                ]
            );

        $builder->addViewTransformer(new MultiCurrencyTransformer());
        $builder->addEventListener(
            FormEvents::POST_SUBMIT,
            function (FormEvent $event) {
                $multiCurrencyHolder = $event->getForm()->getParent()->getData();
                if ($multiCurrencyHolder instanceof MultiCurrencyHolderInterface) {
                    $multiCurrencyHolder->updateMultiCurrencyFields();
                }
            }
        );
    }
}
