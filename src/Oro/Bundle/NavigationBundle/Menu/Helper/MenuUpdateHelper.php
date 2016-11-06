<?php

namespace Oro\Bundle\NavigationBundle\Menu\Helper;

use Doctrine\Common\Collections\Collection;

use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\Translation\TranslatorInterface;

use Oro\Component\PropertyAccess\PropertyAccessor;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\LocaleBundle\Helper\LocalizationHelper;
use Oro\Bundle\NavigationBundle\Entity\MenuUpdateInterface;

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
            $defaultValue = $this->translator->trans($value);
            $this->getPropertyAccessor()->setValue($entity, 'default_' . $name, $defaultValue);
            foreach ($this->localizationHelper->getLocalizations() as $localization) {
                $locale = $localization->getLanguageCode();
                $translatedValue = $this->translator->trans($value, [], null, $locale);
                if ($translatedValue !== $defaultValue) {
                    $fallbackValue = new LocalizedFallbackValue();
                    $fallbackValue->setLocalization($localization);
                    $this->getPropertyAccessor()->setValue($fallbackValue, $type, $translatedValue);

                    $this->getPropertyAccessor()->setValue($entity, $name, [$fallbackValue]);
                }
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
