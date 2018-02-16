<?php

namespace Oro\Bundle\UIBundle\Twig;

use Symfony\Component\DependencyInjection\ContainerInterface;

use Oro\Bundle\UIBundle\Tools\HtmlTagHelper;

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
            new \Twig_SimpleFilter('oro_html_purify', [$this, 'htmlPurify']),
            new \Twig_SimpleFilter('oro_html_sanitize', [$this, 'htmlSanitize'], ['is_safe' => ['html']]),
            new \Twig_SimpleFilter('oro_html_tag_trim', [$this, 'htmlTagTrim'], ['is_safe' => ['html']]),
        ];
    }

    /**
     * @param string $string
     *
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
     * @param string $string
     * @return string
     */
    public function htmlPurify($string)
    {
        return $this->getHtmlTagHelper()->purify($string);
    }

    /**
     * @param string $string
     *
     * @return string
     */
    public function htmlSanitize($string)
    {
        return $this->getHtmlTagHelper()->sanitize($string);
    }

    /**
     * @param string $html
     * @param array $tags
     * @return string
     */
    public function htmlTagTrim($html, array $tags = [])
    {
        foreach ($tags as $tag) {
            $pattern = '/(<' . $tag . '[^>]*>)((.|\s)*?)(<\/' . $tag . '>)|(<' . $tag . '[^>]*>)/i';
            $html = preg_replace($pattern, '', $html);
        }

        return $html;
    }

    /**
     * {@inheritDoc}
     */
    public function getName()
    {
        return 'oro_ui.html_tag';
    }
}
