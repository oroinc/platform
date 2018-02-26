<?php

namespace Oro\Bundle\UIBundle\Twig;

use Oro\Bundle\UIBundle\Tools\HtmlTagHelper;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Twig extension with filters that helps prepare HTML for the output.
 */
class HtmlTagExtension extends \Twig_Extension
{
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
     * @return HtmlTagHelper
     */
    protected function getHtmlTagHelper()
    {
        return $this->container->get('oro_ui.html_tag_helper');
    }

    /**
     * {@inheritDoc}
     */
    public function getFilters()
    {
        return [
            new \Twig_SimpleFilter('oro_tag_filter', [$this, 'tagFilter'], ['is_safe' => ['all']]),
            new \Twig_SimpleFilter('oro_attribute_name_purify', [$this, 'attributeNamePurify']),
            new \Twig_SimpleFilter('oro_html_sanitize', [$this, 'htmlSanitize'], ['is_safe' => ['html']]),
            new \Twig_SimpleFilter('oro_html_tag_trim', [$this, 'htmlTagTrim'], ['is_safe' => ['html']]),
            /**
             * @deprecated Use `oro_tag_filter` instead
             */
            new \Twig_SimpleFilter('oro_html_purify', [$this, 'htmlPurify']),
        ];
    }

    /**
     * Remove all html elements
     *
     * @param string $string
     * @return string
     */
    public function tagFilter($string)
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
        return preg_replace('/[^a-z0-9_-]+/i', '', $string);
    }

    /**
     * Filter is intended to purify script, style etc tags and content of them
     *
     * @deprecated Use `tagFilter` method instead
     *
     * @param string $string
     * @return string
     */
    public function htmlPurify($string)
    {
        return $this->tagFilter($string);
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
     * @param array $tags
     * @return string
     */
    public function htmlTagTrim($string, array $tags = [])
    {
        return $this->getHtmlTagHelper()->escape($string);
    }

    /**
     * {@inheritDoc}
     */
    public function getName()
    {
        return 'oro_ui.html_tag';
    }
}
