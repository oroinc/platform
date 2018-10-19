<?php

namespace Oro\Bundle\CurrencyBundle\Form\Type;

use Oro\Bundle\CurrencyBundle\Provider\CurrencyProviderInterface;
use Oro\Bundle\CurrencyBundle\Provider\ViewTypeProviderInterface;
use Oro\Bundle\CurrencyBundle\Utils\CurrencyNameHelper;
use Oro\Bundle\FormBundle\Utils\FormUtils;
use Oro\Bundle\LocaleBundle\Model\LocaleSettings;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Exception\LogicException;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\Intl\Intl;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Base class for form types that work with currencies.
 */
abstract class AbstractCurrencySelectionType extends AbstractType
{
    /**
     * @var CurrencyProviderInterface
     */
    protected $currencyProvider;

    /**
     * @var LocaleSettings
     */
    protected $localeSettings;

    /**
     * @var string
     */
    protected $currencySelectorConfigKey;

    /**
     * @var CurrencyNameHelper
     */
    protected $currencyNameHelper;

    /**
     * @param CurrencyProviderInterface $currencyProvider
     * @param LocaleSettings $localeSettings
     * @param CurrencyNameHelper $currencyNameHelper
     */
    public function __construct(
        CurrencyProviderInterface $currencyProvider,
        LocaleSettings $localeSettings,
        CurrencyNameHelper $currencyNameHelper
    ) {
        $this->currencyProvider = $currencyProvider;
        $this->localeSettings = $localeSettings;
        $this->currencyNameHelper = $currencyNameHelper;
    }

    /**
     * {@inheritDoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'choices' => function (Options $options) {
                $this->checkOptions($options);

                if ($options['full_currency_list']) {
                    return array_flip($this->currencyNameHelper->getCurrencyFilteredList());
                }

                $currencies = $options['currencies_list'];
                if (empty($currencies)) {
                    $currencies = $this->getCurrencies();
                }

                $currencies = array_merge($currencies, (array)$options['additional_currencies']);

                $this->checkCurrencies($currencies);

                return array_unique($currencies);
            },
            'compact' => false,
            'currencies_list' => null,
            'additional_currencies' => null,
            'full_currency_list' => false,
            'full_currency_name' => false,
        ]);

        $resolver->setNormalizer('choice_label', function (Options $options, $value) {
            $viewType = null;

            if ($options['full_currency_name']) {
                $viewType = ViewTypeProviderInterface::VIEW_TYPE_FULL_NAME;
            }

            if ($options['compact']) {
                $viewType = ViewTypeProviderInterface::VIEW_TYPE_ISO_CODE;
            }

            return function ($currencyCode) use ($viewType) {
                return $this->currencyNameHelper->getCurrencyName($currencyCode, $viewType);
            };
        });
    }

    /**
     * @param FormView $view
     * @param FormInterface $form
     * @param array $options
     */
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $view->vars['hidden_field'] = (count($options['choices']) <= 1);
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) use ($options) {
            $selectedCurrency = $event->getData();
            if (empty($selectedCurrency)) {
                $selectedCurrency = $this->getDefaultCurrency();
                if ($this->isMultiple($options)) {
                    $selectedCurrency = [$selectedCurrency => 0];
                }
                $event->setData($selectedCurrency);
            }
        });

        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) use ($options) {
            $formData = $event->getData();

            if (!empty($formData) && !$this->isMultiple($options) && $this->isMissedCurrency($formData, $options)) {
                FormUtils::replaceField(
                    $event->getForm()->getParent(),
                    $event->getForm()->getName(),
                    ['additional_currencies' => [$formData]],
                    ['choices']
                );
            }
        });
    }

    /**
     * @param Options $options
     * @throws LogicException
     */
    protected function checkOptions(Options $options)
    {
        if (($options['currencies_list'] !== null && !is_array($options['currencies_list']))
            || (is_array($options['currencies_list']) && empty($options['currencies_list']))
        ) {
            throw new LogicException('The option "currencies_list" must be null or not empty array.');
        }

        if ($options['additional_currencies'] !== null && !is_array($options['additional_currencies'])) {
            throw new LogicException('The option "additional_currencies" must be null or array.');
        }
    }

    /**
     * @param array $currencies
     * @throws LogicException
     */
    protected function checkCurrencies(array $currencies)
    {
        $invalidCurrencies = [];

        foreach ($currencies as $currency) {
            $name = Intl::getCurrencyBundle()->getCurrencyName($currency, $this->localeSettings->getLocale());

            if (!$name) {
                $invalidCurrencies[] = $currency;
            }
        }

        if ($invalidCurrencies) {
            throw new LogicException(sprintf('Found unknown currencies: %s.', implode(', ', $invalidCurrencies)));
        }
    }

    /**
     * {@inheritDoc}
     */
    public function getParent()
    {
        return ChoiceType::class;
    }

    /**
     * @return array
     */
    protected function getCurrencies()
    {
        return $this->currencyProvider->getCurrencyList();
    }

    /**
     * @return string
     */
    protected function getDefaultCurrency()
    {
        return $this->currencyProvider->getDefaultCurrency();
    }

    /**
     * @param array $options
     * @return bool
     */
    protected function isMultiple(array $options)
    {
        return isset($options['multiple']) && $options['multiple'];
    }

    /**
     * @param string $currencyCode
     * @param array $options
     * @return bool
     */
    private function isMissedCurrency($currencyCode, array $options)
    {
        return empty($options['choices']) || !in_array($currencyCode, $options['choices'], true);
    }
}
