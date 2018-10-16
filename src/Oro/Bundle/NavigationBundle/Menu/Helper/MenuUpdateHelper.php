<?php

namespace Oro\Bundle\NavigationBundle\Menu\Helper;

use Doctrine\Common\Collections\Collection;
use Oro\Bundle\LocaleBundle\DependencyInjection\Configuration;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\LocaleBundle\Helper\LocalizationHelper;
use Oro\Bundle\LocaleBundle\Model\FallbackType;
use Oro\Bundle\NavigationBundle\Entity\MenuUpdateInterface;
use Oro\Component\PropertyAccess\PropertyAccessor;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * Translates and apply all translations for given values
 */
class MenuUpdateHelper
{
    /** @var TranslatorInterface */
    protected $translator;

    /** @var LocalizationHelper */
    protected $localizationHelper;

    /** @var PropertyAccessor */
    private $propertyAccessor;

    /**
     * @param TranslatorInterface $translator
     * @param LocalizationHelper  $localizationHelper
     */
    public function __construct(TranslatorInterface $translator, LocalizationHelper $localizationHelper)
    {
        $this->translator = $translator;
        $this->localizationHelper = $localizationHelper;
    }

    /**
     * @param MenuUpdateInterface $entity
     * @param string              $value
     * @param string              $name
     * @param string              $type
     *
     * @return MenuUpdateHelper
     */
    public function applyLocalizedFallbackValue(MenuUpdateInterface $entity, $value, $name, $type)
    {
        $values = $this->getPropertyAccessor()->getValue($entity, $name . 's');
        if ($values instanceof Collection && $values->count() <= 0) {
            // Default translation for menu must always has value for English locale, because out of the box app has
            // translations only for English language.
            $defaultValue = $this->translator->trans($value, [], null, Configuration::DEFAULT_LOCALE);
            $this->getPropertyAccessor()->setValue($entity, 'default_' . $name, $defaultValue);
            foreach ($this->localizationHelper->getLocalizations() as $localization) {
                $locale = $localization->getLanguageCode();
                $translatedValue = $this->translator->trans($value, [], null, $locale);
                $fallbackValue = new LocalizedFallbackValue();
                $fallbackValue->setLocalization($localization);

                // If value for current localization is equal to default value - fallback must be set to "default value"
                if ($translatedValue === $defaultValue) {
                    $fallbackValue->setFallback(FallbackType::SYSTEM);
                } else {
                    $this->getPropertyAccessor()->setValue($fallbackValue, $type, $translatedValue);
                }

                $this->getPropertyAccessor()->setValue($entity, $name, [$fallbackValue]);
            }
        }

        return $this;
    }

    /**
     * @return PropertyAccessor
     */
    private function getPropertyAccessor()
    {
        if (!$this->propertyAccessor) {
            $this->propertyAccessor = PropertyAccess::createPropertyAccessor();
        }

        return $this->propertyAccessor;
    }
}
