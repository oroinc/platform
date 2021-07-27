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
 *   - oro_html_escape
 */
class HtmlTagExtension extends AbstractExtension implements ServiceSubscriberInterface
{
    private ContainerInterface $container;
    private ?HtmlTagHelper $htmlTagHelper = null;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * {@inheritDoc}
     */
    public function getFilters()
    {
        return [
            new TwigFilter('oro_html_strip_tags', [$this, 'htmlStripTags'], ['is_safe' => ['all']]),
            new TwigFilter('oro_attribute_name_purify', [$this, 'attributeNamePurify']),
            new TwigFilter('oro_html_sanitize', [$this, 'htmlSanitize'], ['is_safe' => ['html']]),
            new TwigFilter('oro_html_escape', [$this, 'htmlEscape'], ['is_safe' => ['html']]),
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
     * Allow HTML tags all forbidden tags will be escaped
     *
     * @param string $string
     * @return string
     */
    public function htmlEscape($string)
    {
        return $this->getHtmlTagHelper()->escape($string);
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedServices()
    {
        return [
            'oro_ui.html_tag_helper' => HtmlTagHelper::class,
        ];
    }

    private function getHtmlTagHelper(): HtmlTagHelper
    {
        if (null === $this->htmlTagHelper) {
            $this->htmlTagHelper = $this->container->get('oro_ui.html_tag_helper');
        }

        return $this->htmlTagHelper;
    }
}
