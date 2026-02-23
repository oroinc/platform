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
    public function __construct(
        private readonly ContainerInterface $container
    ) {
    }

    #[\Override]
    public function getFilters()
    {
        return [
            new TwigFilter('oro_language_code_title', [$this, 'getLanguageTitleByCode']),
            new TwigFilter('oro_locale_code_title', [$this, 'formatLocale']),
            new TwigFilter('oro_formatting_code_title', [$this, 'getFormattingTitleByCode']),
            new TwigFilter('localized_value', [$this, 'getLocalizedValue'], ['is_safe' => ['html']]),
        ];
    }

    public function getLanguageTitleByCode(?string $code): ?string
    {
        return $this->getLanguageCodeFormatter()->format($code);
    }

    public function formatLocale(?string $code): ?string
    {
        return $this->getLanguageCodeFormatter()->formatLocale($code);
    }

    public function getFormattingTitleByCode(?string $code): ?string
    {
        return $this->getFormattingCodeFormatter()->format($code);
    }

    public function getLocalizedValue(Collection $values, ?Localization $localization = null): string
    {
        return (string)$this->getLocalizationHelper()->getLocalizedValue($values, $localization);
    }

    #[\Override]
    public static function getSubscribedServices(): array
    {
        return [
            LanguageCodeFormatter::class,
            FormattingCodeFormatter::class,
            LocalizationHelper::class
        ];
    }

    private function getLanguageCodeFormatter(): LanguageCodeFormatter
    {
        return $this->container->get(LanguageCodeFormatter::class);
    }

    private function getFormattingCodeFormatter(): FormattingCodeFormatter
    {
        return $this->container->get(FormattingCodeFormatter::class);
    }

    private function getLocalizationHelper(): LocalizationHelper
    {
        return $this->container->get(LocalizationHelper::class);
    }
}
