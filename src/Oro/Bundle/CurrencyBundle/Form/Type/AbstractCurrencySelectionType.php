<?php

namespace Oro\Bundle\CurrencyBundle\Form\Type;

use Oro\Bundle\CurrencyBundle\Provider\ViewTypeProviderInterface;
use Oro\Bundle\FormBundle\Utils\FormUtils;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Exception\LogicException;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\Intl\Intl;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Oro\Bundle\CurrencyBundle\Config\CurrencyConfigInterface;
use Oro\Bundle\CurrencyBundle\Utils\CurrencyNameHelper;
use Oro\Bundle\LocaleBundle\Model\LocaleSettings;

abstract class AbstractCurrencySelectionType extends AbstractType
{
    /**
     * $var CurrencyConfigInterface
     */
    protected $currencyConfig;
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
     * @param CurrencyConfigInterface $currencyConfig
     * @param LocaleSettings $localeSettings
     * @param CurrencyNameHelper $currencyNameHelper
     */
    public function __construct(
        CurrencyConfigInterface $currencyConfig,
        LocaleSettings $localeSettings,
        CurrencyNameHelper $currencyNameHelper
    ) {
        $this->currencyConfig       = $currencyConfig;
        $this->localeSettings       = $localeSettings;
        $this->currencyNameHelper   = $currencyNameHelper;
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
                    return $this->currencyNameHelper->getCurrencyFilteredList();
                }

                $currencies = $options['currencies_list'];
                if (!count($currencies)) {
                    $currencies = $this->getCurrencies();
                }

                $currencies = array_merge($currencies, (array)$options['additional_currencies']);

                $this->checkCurrencies($currencies);

                return array_flip($currencies);
            },
            'compact' => false,
            'currencies_list' => null,
            'additional_currencies' => null,
            'full_currency_list' => false,
        ]);

        $resolver->setNormalizer('choice_label', function (Options $options, $value) {
            $viewType = null;

            if ($options['compact']) {
                $viewType = ViewTypeProviderInterface::VIEW_TYPE_ISO_CODE;
            }

            return function ($currencyCode) use ($viewType) {
                return $this->currencyNameHelper->getCurrencyName($currencyCode, $viewType);
            };
        });
    }

    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $view->vars['hidden_field'] = (count($options['choices']) <= 1);
    }

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
                    ['choice_list', 'choices']
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
        return 'choice';
    }

    /**
     * @return array
     */
    protected function getCurrencies()
    {
        return $this->currencyConfig->getCurrencyList();
    }

    /**
     * @return string
     */
    protected function getDefaultCurrency()
    {
        return $this->currencyConfig->getDefaultCurrency();
    }

    protected function isMultiple($options)
    {
        return isset($options['multiple']) && $options['multiple'];
    }

    private function isMissedCurrency($currencyCode, $options)
    {
        return (empty($options['choices']) || !isset($options['choices'][$currencyCode]));
    }
}
