<?php

namespace Oro\Bundle\UIBundle\Twig;

use Oro\Bundle\UIBundle\Tools\HtmlTagHelper;
use Psr\Container\ContainerInterface;
use Symfony\Contracts\Service\ServiceSubscriberInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

/**
 * Provides Twig filters for HTML output preparation:
 *   - oro_html_strip_tags
 *   - oro_attribute_name_purify
 *   - oro_html_sanitize
 *   - oro_html_sanitize_basic
 *   - oro_html_escape
 */
class HtmlTagExtension extends AbstractExtension implements ServiceSubscriberInterface
{
    public function __construct(
        private readonly ContainerInterface $container
    ) {
    }

    #[\Override]
    public function getFilters()
    {
        return [
            new TwigFilter('oro_html_strip_tags', [$this, 'htmlStripTags'], ['is_safe' => ['all']]),
            new TwigFilter('oro_attribute_name_purify', [$this, 'attributeNamePurify']),
            new TwigFilter('oro_html_sanitize', [$this, 'htmlSanitize'], ['is_safe' => ['html']]),
            new TwigFilter('oro_html_escape', [$this, 'htmlEscape'], ['is_safe' => ['html']]),
            new TwigFilter('oro_html_sanitize_basic', [$this, 'htmlSanitizeBasic'], ['is_safe' => ['html']]),
        ];
    }

    /**
     * Remove all html elements
     *
     * @param string $string
     * @return string
     */
    public function htmlStripTags($string)
    {
        return $this->getHtmlTagHelper()->stripTags($string);
    }

    /**
     * Remove all non alpha-numeric symbols
     *
     * @param string $string
     * @return string
     */
    public function attributeNamePurify($string)
    {
        return preg_replace('/[^a-z0-9\_\-]+/i', '', $string);
    }

    /**
     * Remove html elements except allowed
     *
     * @param string $string
     * @return string
     */
    public function htmlSanitize($string)
    {
        return $this->getHtmlTagHelper()->sanitize($string);
    }

    /**
     * Remove html elements except allowed in the "basic" HTML purification mode.
     *
     * @param string|null $string
     *
     * @return string
     */
    public function htmlSanitizeBasic(?string $string): string
    {
        if ($string === null) {
            return '';
        }

        return $this->getHtmlTagHelper()->sanitize($string, 'basic', false);
    }

    /**
     * Allow HTML tags all forbidden tags will be escaped
     *
     * @param string $string
     * @return string
     */
    public function htmlEscape($string)
    {
        return $this->getHtmlTagHelper()->escape($string);
    }

    #[\Override]
    public static function getSubscribedServices(): array
    {
        return [
            HtmlTagHelper::class
        ];
    }

    private function getHtmlTagHelper(): HtmlTagHelper
    {
        return $this->container->get(HtmlTagHelper::class);
    }
}
