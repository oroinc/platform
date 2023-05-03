<?php

namespace Oro\Bundle\NavigationBundle\Menu\Helper;

use Doctrine\Common\Collections\Collection;
use Oro\Bundle\EntityExtendBundle\PropertyAccess;
use Oro\Bundle\LocaleBundle\DependencyInjection\Configuration;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\LocaleBundle\Helper\LocalizationHelper;
use Oro\Bundle\LocaleBundle\Model\FallbackType;
use Oro\Bundle\NavigationBundle\Entity\MenuUpdateInterface;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Translates and apply all translations for given values
 */
class MenuUpdateHelper
{
    /** @var TranslatorInterface */
    protected $translator;

    /** @var LocalizationHelper */
    protected $localizationHelper;

    protected ?PropertyAccessorInterface $propertyAccessor = null;

    public function __construct(TranslatorInterface $translator, LocalizationHelper $localizationHelper)
    {
        $this->translator = $translator;
        $this->localizationHelper = $localizationHelper;
    }

    /**
     * @param MenuUpdateInterface $entity
     * @param string|null $value
     * @param string $name
     * @param string $type
     *
     * @return MenuUpdateHelper
     */
    public function applyLocalizedFallbackValue(
        MenuUpdateInterface $entity,
        ?string $value,
        string $name,
        string $type
    ) {
        $values = $this->getPropertyAccessor()->getValue($entity, $name . 's');
        if ($values instanceof Collection && $values->count() <= 0) {
            $value = (string) $value;
            $doTranslation = $value !== '';

            // Default translation for menu must always have value for English locale, because out of the box app has
            // translations only for English language.
            $defaultValue = $doTranslation
                ? $this->translator->trans($value, [], null, Configuration::DEFAULT_LOCALE)
                : $value;
            $defaultFallbackValue = new LocalizedFallbackValue();
            $this->getPropertyAccessor()->setValue($defaultFallbackValue, $type, $defaultValue);

            $fallbackValues = [$defaultFallbackValue];
            foreach ($this->localizationHelper->getLocalizations() as $localization) {
                $locale = $localization->getLanguageCode();
                $translatedValue = $doTranslation ? $this->translator->trans($value, [], null, $locale) : $value;
                $fallbackValue = new LocalizedFallbackValue();
                $fallbackValue->setLocalization($localization);

                // If value for current localization is equal to default value - fallback must be set to "default value"
                if ($translatedValue === $defaultValue) {
                    $fallbackValue->setFallback(FallbackType::SYSTEM);
                } else {
                    $this->getPropertyAccessor()->setValue($fallbackValue, $type, $translatedValue);
                }

                $fallbackValues[] = $fallbackValue;
            }

            $this->getPropertyAccessor()->setValue($entity, $name, $fallbackValues);
        }

        return $this;
    }

    /**
     * @return PropertyAccessorInterface
     */
    private function getPropertyAccessor()
    {
        if (!$this->propertyAccessor) {
            $this->propertyAccessor = PropertyAccess::createPropertyAccessor();
        }

        return $this->propertyAccessor;
    }
}
