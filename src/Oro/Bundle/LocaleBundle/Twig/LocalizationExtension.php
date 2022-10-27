<?php

namespace Oro\Bundle\LocaleBundle\Twig;

use Doctrine\Common\Collections\Collection;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\Formatter\FormattingCodeFormatter;
use Oro\Bundle\LocaleBundle\Formatter\LanguageCodeFormatter;
use Oro\Bundle\LocaleBundle\Helper\LocalizationHelper;
use Psr\Container\ContainerInterface;
use Symfony\Contracts\Service\ServiceSubscriberInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

/**
 * Provides Twig filters to format language and locale codes, and to retrieve the value in the specified localization
 * from a localized value holder:
 *   - oro_language_code_title
 *   - oro_locale_code_title
 *   - oro_formatting_code_title
 *   - localized_value
 */
class LocalizationExtension extends AbstractExtension implements ServiceSubscriberInterface
{
    private ContainerInterface $container;
    private ?LanguageCodeFormatter $languageCodeFormatter = null;
    private ?FormattingCodeFormatter $formattingCodeFormatter = null;
    private ?LocalizationHelper $localizationHelper = null;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * {@inheritdoc}
     */
    public function getFilters()
    {
        return [
            new TwigFilter('oro_language_code_title', [$this, 'getLanguageTitleByCode']),
            new TwigFilter('oro_locale_code_title', [$this, 'formatLocale']),
            new TwigFilter('oro_formatting_code_title', [$this, 'getFormattingTitleByCode']),
            new TwigFilter('localized_value', [$this, 'getLocalizedValue'], ['is_safe' => ['html']]),
        ];
    }

    /**
     * @param string $code
     *
     * @return string
     */
    public function getLanguageTitleByCode($code)
    {
        return $this->getLanguageCodeFormatter()->format($code);
    }

    /**
     * @param string $code
     *
     * @return string
     */
    public function formatLocale($code)
    {
        return $this->getLanguageCodeFormatter()->formatLocale($code);
    }

    /**
     * @param string $code
     *
     * @return string
     */
    public function getFormattingTitleByCode($code)
    {
        return $this->getFormattingCodeFormatter()->format($code);
    }

    public function getLocalizedValue(Collection $values, Localization $localization = null): string
    {
        return (string)$this->getLocalizationHelper()->getLocalizedValue($values, $localization);
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedServices()
    {
        return [
            'oro_locale.formatter.language_code' => LanguageCodeFormatter::class,
            'oro_locale.formatter.formatting_code' => FormattingCodeFormatter::class,
            'oro_locale.helper.localization' => LocalizationHelper::class,
        ];
    }

    private function getLanguageCodeFormatter(): LanguageCodeFormatter
    {
        if (null === $this->languageCodeFormatter) {
            $this->languageCodeFormatter = $this->container->get('oro_locale.formatter.language_code');
        }

        return $this->languageCodeFormatter;
    }

    private function getFormattingCodeFormatter(): FormattingCodeFormatter
    {
        if (null === $this->formattingCodeFormatter) {
            $this->formattingCodeFormatter = $this->container->get('oro_locale.formatter.formatting_code');
        }

        return $this->formattingCodeFormatter;
    }

    private function getLocalizationHelper(): LocalizationHelper
    {
        if (null === $this->localizationHelper) {
            $this->localizationHelper = $this->container->get('oro_locale.helper.localization');
        }

        return $this->localizationHelper;
    }
}
