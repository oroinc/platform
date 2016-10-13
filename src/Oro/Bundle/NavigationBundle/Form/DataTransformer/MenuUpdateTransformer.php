<?php

namespace Oro\Bundle\NavigationBundle\Form\DataTransformer;

use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\LocaleBundle\Helper\LocalizationHelper;
use Oro\Bundle\NavigationBundle\Entity\MenuUpdateInterface;

use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessor;
use Symfony\Component\Translation\TranslatorInterface;

class MenuUpdateTransformer implements DataTransformerInterface
{
    /** @var TranslatorInterface */
    private $translator;

    /** @var LocalizationHelper */
    private $localizationHelper;

    /** @var PropertyAccessor */
    private $propertyAccessor;

    /**
     * @param TranslatorInterface $translator
     * @param LocalizationHelper $localizationHelper
     */
    public function __construct(TranslatorInterface $translator, LocalizationHelper $localizationHelper)
    {
        $this->translator = $translator;
        $this->localizationHelper = $localizationHelper;
    }

    /**
     * {@inheritdoc}
     */
    public function transform($value)
    {
        if ($value instanceof MenuUpdateInterface) {
            $this->translateField($value, 'title');
        }

        return $value;
    }

    /**
     * {@inheritdoc}
     */
    public function reverseTransform($value)
    {
        return $value;
    }

    /**
     * @param MenuUpdateInterface $menuUpdate
     * @param string              $field
     */
    private function translateField(MenuUpdateInterface $menuUpdate, $field)
    {
        $value = $this->getPropertyAccessor()->getValue($menuUpdate, 'default_' . $field);

        $translatedValue = $this->getTranslatedValue($value);
        if (!$translatedValue) {
            return;
        }

        $this->getPropertyAccessor()->setValue($menuUpdate, 'default_' . $field, $translatedValue);

        foreach ($this->localizationHelper->getLocalizations() as $localization) {
            $translatedValue = $this->getTranslatedValue($value, $localization->getLanguageCode());
            if ($translatedValue) {
                $fallbackValue = new LocalizedFallbackValue();
                $fallbackValue->setLocalization($localization);
                $fallbackValue->setString($translatedValue);

                $this->getPropertyAccessor()->setValue($menuUpdate, $field, [$fallbackValue]);
            }
        }

    }

    /**
     * Return translated value if translation contains
     *
     * @param string      $value
     * @param string|null $locale
     *
     * @return null|string
     */
    private function getTranslatedValue($value, $locale = null)
    {
        $translatedValue = $this->translator->trans($value, [], null, $locale);
        if ($translatedValue != $value) {
            return $translatedValue;
        }

        return null;
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
