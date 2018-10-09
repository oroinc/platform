<?php

namespace Oro\Bundle\LocaleBundle\Helper;

use Doctrine\Common\Collections\Collection;

use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\Entity\FallbackTrait;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\LocaleBundle\Manager\LocalizationManager;
use Oro\Bundle\LocaleBundle\Provider\CurrentLocalizationProvider;
use Oro\Bundle\LocaleBundle\Provider\LocalizationProviderInterface;

/**
 * Handles logic wo work with localization
 * ! This class is added for BC purpose to make possible to use LocalizationProviderInterface in constructor
 */
class ConfigDefaultLocalizationHelper extends LocalizationHelper
{
    /**
     * @var LocalizationProviderInterface
     */
    protected $currentLocalizationProvider;

    /**
     * @param LocalizationManager $localizationManager
     * @param LocalizationProviderInterface $currentLocalizationProvider
     */
    public function __construct(
        LocalizationManager $localizationManager,
        LocalizationProviderInterface $currentLocalizationProvider
    ) {
        $this->localizationManager = $localizationManager;
        $this->currentLocalizationProvider = $currentLocalizationProvider;
    }
}
