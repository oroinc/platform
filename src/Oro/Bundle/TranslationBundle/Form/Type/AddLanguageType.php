<?php
declare(strict_types=1);

namespace Oro\Bundle\TranslationBundle\Form\Type;

use Oro\Bundle\FormBundle\Form\Type\OroChoiceType;
use Oro\Bundle\LocaleBundle\Model\LocaleSettings;
use Oro\Bundle\TranslationBundle\Download\TranslationMetricsProviderInterface;
use Oro\Bundle\TranslationBundle\Entity\Repository\LanguageRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Intl\Locales;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Form type for selection of a new language out of the list of all available languages.
 * The languages that have translations available on the translation service, are shown in a separate options group.
 */
class AddLanguageType extends AbstractType
{
    private LanguageRepository $languageRepository;
    private LocaleSettings $localeSettings;
    private TranslationMetricsProviderInterface $translationStatisticProvider;
    private TranslatorInterface $translator;

    public function __construct(
        LanguageRepository $languageRepository,
        LocaleSettings $localeSettings,
        TranslationMetricsProviderInterface $translationStatisticProvider,
        TranslatorInterface $translator
    ) {
        $this->languageRepository = $languageRepository;
        $this->localeSettings = $localeSettings;
        $this->translationStatisticProvider = $translationStatisticProvider;
        $this->translator = $translator;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'choices' => $this->getLanguageChoices(),
                'placeholder' => 'oro.translation.language.form.select.empty_value',
            ]
        );
    }

    public function getParent()
    {
        return OroChoiceType::class;
    }

    public function getBlockPrefix()
    {
        return 'oro_translation_add_language';
    }

    private function getLanguageChoices(): array
    {
        $installed = $this->languageRepository->getAvailableLanguageCodesAsArrayKeys();
        $availableWithTranslations = [];
        $availableWithoutTranslations = [];
        $translationMetrics = $this->translationStatisticProvider->getAll();

        foreach (Locales::getNames($this->localeSettings->getLanguage()) as $langCode => $langName) {
            if (isset($installed[$langCode])) {
                continue;
            }
            if (isset($translationMetrics[$langCode])) {
                $availableWithTranslations[sprintf('%s - %s', $langName, $langCode)] = $langCode;
            } else {
                $availableWithoutTranslations[sprintf('%s - %s', $langName, $langCode)] = $langCode;
            }
        }

        return [
            $this->translator->trans('oro.translation.language.form.select.group.crowdin')
            => $availableWithTranslations,
            $this->translator->trans('oro.translation.language.form.select.group.intl')
            => $availableWithoutTranslations
        ];
    }
}
