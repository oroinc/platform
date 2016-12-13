<?php

namespace Oro\Bundle\CurrencyBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Oro\Bundle\CurrencyBundle\Form\DataTransformer\PriceTransformer;
use Oro\Bundle\CurrencyBundle\Rounding\RoundingServiceInterface;

class PriceType extends AbstractType
{
    const NAME = 'oro_currency_price';
    const OPTIONAL_VALIDATION_GROUP = 'Optional';

    /**
     * @var string
     */
    protected $dataClass;

    /**
     * @var RoundingServiceInterface
     */
    protected $roundingService;

    /**
     * @param RoundingServiceInterface $roundingService
     */
    public function __construct(RoundingServiceInterface $roundingService)
    {
        $this->roundingService = $roundingService;
    }

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
            $currencyType = CurrencySelectionType::NAME;
            $currencyOptions = [
                'additional_currencies' => $options['additional_currencies'],
                'currencies_list' => $options['currencies_list'],
                'full_currency_list' => $options['full_currency_list'],
                'compact' => $options['compact'],
                'required' => $isRequiredPrice,
                'empty_value' => $options['currency_empty_value'],
            ];
        } else {
            $currencyType = 'hidden';
            $currencyOptions = [
                'data' => $options['default_currency']
            ];
        }

        $builder
            ->add(
                'value',
                'number',
                [
                    'required' => $isRequiredPrice,
                    'scale' => $this->roundingService->getPrecision(),
                    'rounding_mode' => $this->roundingService->getRoundType(),
                    'attr' => ['data-scale' => $this->roundingService->getPrecision()]
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
            'validation_groups'=> ['Default']
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
