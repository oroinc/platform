<?php

namespace Oro\Bundle\LocaleBundle\Twig;

use Doctrine\Common\Collections\Collection;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\Formatter\FormattingCodeFormatter;
use Oro\Bundle\LocaleBundle\Formatter\LanguageCodeFormatter;
use Oro\Bundle\LocaleBundle\Helper\LocalizationHelper;
use Symfony\Component\DependencyInjection\ContainerInterface;

class LocalizationExtension extends \Twig_Extension
{
    const NAME = 'oro_locale_localization';

    /** @var ContainerInterface */
    protected $container;

    /**
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * @return LanguageCodeFormatter
     */
    protected function getLanguageCodeFormatter()
    {
        return $this->container->get('oro_locale.formatter.language_code');
    }

    /**
     * @return FormattingCodeFormatter
     */
    protected function getFormattingCodeFormatter()
    {
        return $this->container->get('oro_locale.formatter.formatting_code');
    }

    /**
     * @return LocalizationHelper
     */
    protected function getLocalizationHelper()
    {
        return $this->container->get('oro_locale.helper.localization');
    }

    /**
     * {@inheritdoc}
     */
    public function getFilters()
    {
        return [
            new \Twig_SimpleFilter(
                'oro_language_code_title',
                [$this, 'getLanguageTitleByCode'],
                ['is_safe' => ['html']]
            ),
            new \Twig_SimpleFilter(
                'oro_locale_code_title',
                [$this, 'formatLocale'],
                ['is_safe' => ['html']]
            ),
            new \Twig_SimpleFilter(
                'oro_formatting_code_title',
                [$this, 'getFormattingTitleByCode'],
                ['is_safe' => ['html']]
            ),
            new \Twig_SimpleFilter(
                'localized_value',
                [$this, 'getLocalizedValue'],
                ['is_safe' => ['html']]
            ),
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

    /**
     * @param Collection        $values
     * @param Localization|null $localization
     *
     * @return string
     */
    public function getLocalizedValue(Collection $values, Localization $localization = null)
    {
        return (string)$this->getLocalizationHelper()->getLocalizedValue($values, $localization);
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return static::NAME;
    }
}
