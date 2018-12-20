<?php

namespace Oro\Bundle\TranslationBundle\Form\Type;

use Oro\Bundle\FormBundle\Form\Type\OroChoiceType;
use Oro\Bundle\LocaleBundle\Model\LocaleSettings;
use Oro\Bundle\TranslationBundle\Entity\Repository\LanguageRepository;
use Oro\Bundle\TranslationBundle\Provider\TranslationStatisticProvider;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Intl\Intl;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatorInterface;

class AddLanguageType extends AbstractType
{
    const NAME = 'oro_translation_add_language';

    /** @var LanguageRepository */
    protected $languageRepository;

    /** @var LocaleSettings */
    protected $localeSettings;

    /** @var TranslationStatisticProvider */
    protected $translationStatisticProvider;

    /** @var TranslatorInterface */
    protected $translator;

    /**
     * @param LanguageRepository $languageRepository
     * @param LocaleSettings $localeSettings
     * @param TranslationStatisticProvider $translationStatisticProvider
     * @param TranslatorInterface $translator
     */
    public function __construct(
        LanguageRepository $languageRepository,
        LocaleSettings $localeSettings,
        TranslationStatisticProvider $translationStatisticProvider,
        TranslatorInterface $translator
    ) {
        $this->languageRepository = $languageRepository;
        $this->localeSettings = $localeSettings;
        $this->translationStatisticProvider = $translationStatisticProvider;
        $this->translator = $translator;
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'choices' => $this->getLanguageChoices(),
                'placeholder' => 'oro.translation.language.form.select.empty_value',
            ]
        );
    }

    /**
     * @return array
     */
    protected function getLanguageChoices()
    {
        $systemLocale = $this->localeSettings->getLanguage();

        $crowdinLangs = [];
        foreach ($this->translationStatisticProvider->get() as $info) {
            $crowdinLangs[$info['code']] = $this->resolveLanguageTitle($systemLocale, $info['code'], $info['realCode']);
        }

        $oroLangs = array_flip($this->languageRepository->getAvailableLanguageCodes());
        // Remove already enabled languages
        $crowdinLangs = array_diff_key($crowdinLangs, $oroLangs);
        // Remove crowdin langs from intl list
        $intlLangs = array_diff_key(Intl::getLocaleBundle()->getLocaleNames($systemLocale), $oroLangs, $crowdinLangs);

        asort($crowdinLangs);
        array_walk($crowdinLangs, [$this, 'formatLanguageLabel']);
        array_walk($intlLangs, [$this, 'formatLanguageLabel']);

        return [
            $this->translator->trans('oro.translation.language.form.select.group.crowdin')
                => array_flip($crowdinLangs),
            $this->translator->trans('oro.translation.language.form.select.group.intl')
                => array_flip($intlLangs),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return OroChoiceType::class;
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
     * @param string $systemLocale
     * @param string $langCode
     * @param string|null $realCode
     *
     * @return null|string
     */
    private function resolveLanguageTitle($systemLocale, $langCode, $realCode = null)
    {
        // Check if 'Intl' has title for $langCode
        $langTitle = Intl::getLocaleBundle()->getLocaleName($langCode, $systemLocale);

        if ($langTitle) {
            return $langTitle;
        }

        if (null !== $realCode) {
            // Check if 'Intl' has title for $realCode
            $langTitle = Intl::getLanguageBundle()->getLanguageName($realCode, null, $systemLocale);

            if ($langTitle) {
                return $langTitle;
            }
        }

        return $langCode;
    }

    /**
     * @param string $label
     * @param string $key
     */
    protected function formatLanguageLabel(&$label, $key)
    {
        $label = sprintf('%s - %s', $label, $key);
    }
}
