<?php

namespace Oro\Bundle\TranslationBundle\Form\Type;

use Symfony\Component\Intl\Intl;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Oro\Bundle\LocaleBundle\Model\LocaleSettings;
use Oro\Bundle\TranslationBundle\Entity\Repository\LanguageRepository;

class AddLanguageType extends AbstractType
{
    /** @var LanguageRepository */
    protected $languageRepository;

    /** @var LocaleSettings */
    protected $localeSettings;

    /**
     * @param LanguageRepository $languageRepository
     * @param LocaleSettings $localeSettings
     */
    public function __construct(LanguageRepository $languageRepository, LocaleSettings $localeSettings)
    {
        $this->languageRepository = $languageRepository;
        $this->localeSettings = $localeSettings;
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'choices'     => $this->getLanguageChoices(),
                'empty_value' => 'Please select...',
            ]
        );
    }

    /**
     * @return array
     */
    protected function getLanguageChoices()
    {
        $allLanguages = Intl::getLocaleBundle()->getLocaleNames($this->localeSettings->getLanguage());
        $codes = $this->languageRepository->getAvailableLanguageCodes();

        return array_diff(array_flip($allLanguages), $codes);
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return 'locale';
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
        return 'oro_translation_add_language';
    }
}
