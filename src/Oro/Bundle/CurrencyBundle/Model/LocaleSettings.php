<?php

namespace Oro\Bundle\CurrencyBundle\Model;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\CurrencyBundle\Provider\ViewTypeProviderInterface;
use Oro\Bundle\LocaleBundle\Model\CalendarFactoryInterface;
use Oro\Bundle\LocaleBundle\Model\LocaleSettings as BaseLocaleSettings;

class LocaleSettings extends BaseLocaleSettings
{
    /**
     * @var ViewTypeProviderInterface
     */
    protected $viewTypeProvider;

    /**
     * LocaleSettings constructor.
     * @param ConfigManager $configManager
     * @param CalendarFactoryInterface $calendarFactory
     * @param ViewTypeProviderInterface $viewTypeProvider
     */
    public function __construct(
        ConfigManager $configManager,
        CalendarFactoryInterface $calendarFactory,
        ViewTypeProviderInterface $viewTypeProvider
    ) {
        parent::__construct($configManager, $calendarFactory);
        $this->viewTypeProvider = $viewTypeProvider;
    }

    /**
     * {@inheritdoc}
     */
    public function getCurrencySymbolByCurrency($currencyCode = null)
    {
        if ($this->viewTypeProvider->getViewType() === ViewTypeProviderInterface::VIEW_TYPE_ISO_CODE) {
            return $currencyCode;
        }

        return parent::getCurrencySymbolByCurrency($currencyCode);
    }
}
