<?php

namespace Oro\Bundle\LocaleBundle\Helper;

use Doctrine\Common\Collections\Collection;
use Oro\Bundle\LocaleBundle\Entity\FallbackTrait;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\LocaleBundle\Manager\LocalizationManager;
use Oro\Bundle\LocaleBundle\Provider\LocalizationProviderInterface;

/**
 * Handles logic to work with localization
 */
class LocalizationHelper
{
    use FallbackTrait;

    /**
     * @var LocalizationManager
     */
    protected $localizationManager;

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

    /**
     * @return Localization
     */
    public function getCurrentLocalization()
    {
        return $this->currentLocalizationProvider->getCurrentLocalization();
    }

    /**
     * @return Localization[]
     */
    public function getLocalizations()
    {
        return $this->localizationManager->getLocalizations();
    }

    /**
     * @param Collection|LocalizedFallbackValue[] $values
     * @param Localization|null $localization
     * @return LocalizedFallbackValue
     */
    public function getLocalizedValue(Collection $values, Localization $localization = null)
    {
        return $this->getFallbackValue($values, $localization ?: $this->getCurrentLocalization());
    }

    /**
     * @param Collection $values
     * @return LocalizedFallbackValue|null
     */
    public function getFirstNonEmptyLocalizedValue(Collection $values)
    {
        // Check default value
        $nonEmptyValue = $this->getDefaultFallbackValue($values);
        $data = null;
        if ($nonEmptyValue) {
            $data = !$this->isEmptyString($nonEmptyValue->getString()) ?: $nonEmptyValue->getText();
        }

        // Then search in collection
        if ($this->isEmptyString($data)) {
            foreach ($values as $value) {
                $data = !$this->isEmptyString($value->getString()) ?: $value->getText();
                if (!$this->isEmptyString($data)) {
                    $nonEmptyValue = $value;
                    break;
                }
            }
        }

        return $nonEmptyValue;
    }

    /**
     * @param string $value
     * @return bool
     */
    private function isEmptyString($value)
    {
        return $value === '' || $value === null;
    }
}
