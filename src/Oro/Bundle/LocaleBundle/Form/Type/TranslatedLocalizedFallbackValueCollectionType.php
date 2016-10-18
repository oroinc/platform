<?php

namespace Oro\Bundle\LocaleBundle\Form\Type;

use Doctrine\Common\Collections\Collection;

use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Component\PropertyAccess\PropertyAccessor;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\Translation\TranslatorInterface;

class TranslatedLocalizedFallbackValueCollectionType extends AbstractType
{
    const NAME = 'oro_locale_translated_localized_fallback_value_collection';

    /** @var TranslatorInterface */
    private $translator;

    /** @var string */
    private $defaultLocale;

    /** @var PropertyAccessor */
    private $propertyAccessor;

    /**
     * @param TranslatorInterface $translator
     * @param string              $defaultLocale
     */
    public function __construct(TranslatorInterface $translator, $defaultLocale)
    {
        $this->translator = $translator;
        $this->defaultLocale = $defaultLocale;
    }

    /**
     * {@inheritdoc}
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
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addEventListener(FormEvents::POST_SET_DATA, [$this, 'translateFallbackValues']);
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return LocalizedFallbackValueCollectionType::class;
    }

    /**
     * Translate localized fallback value collection
     *
     * @param FormEvent $event
     */
    public function translateFallbackValues(FormEvent $event)
    {
        $collection = $event->getData();
        if (!$collection instanceof Collection) {
            return;
        }

        $field = $event->getForm()->getConfig()->getOption('field');

        foreach ($collection as $fallbackValue) {
            if (!$fallbackValue instanceof LocalizedFallbackValue) {
                throw new \InvalidArgumentException(sprintf(
                    'ArrayCollection must contain only "%s".',
                    LocalizedFallbackValue::class
                ));
            }

            if ($fallbackValue->getId()) {
                continue;
            }

            $localization = $fallbackValue->getLocalization();
            $locale = $localization ? $localization->getLanguageCode() : $this->defaultLocale;

            $value = $this->getPropertyAccessor()->getValue($fallbackValue, $field);

            $translatedValue = $this->getTranslatedValue($value, $locale);

            $this->getPropertyAccessor()->setValue($fallbackValue, $field, $translatedValue);
        }
    }

    /**
     * Return translated value
     *
     * @param string      $value
     * @param string|null $locale
     *
     * @return string
     */
    private function getTranslatedValue($value, $locale = null)
    {
        return $this->translator->trans($value, [], null, $locale);
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
