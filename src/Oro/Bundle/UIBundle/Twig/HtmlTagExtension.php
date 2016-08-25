<?php

namespace Oro\Bundle\UIBundle\Twig;

use Oro\Bundle\FormBundle\Provider\HtmlTagProvider;
use Oro\Bundle\UIBundle\Tools\HtmlTagHelper;

class HtmlTagExtension extends \Twig_Extension
{
    /** @var HtmlTagProvider */
    protected $htmlTagProvider;

    /** @var HtmlTagHelper */
    protected $htmlTagHelper;

    /**
     * @param HtmlTagHelper $htmlTagHelper
     */
    public function __construct(HtmlTagHelper $htmlTagHelper)
    {
        $this->htmlTagHelper = $htmlTagHelper;
    }

    /**
     * {@inheritDoc}
     */
    public function getFilters()
    {
        return [
            new \Twig_SimpleFilter('oro_tag_filter', [$this, 'tagFilter'], ['is_safe' => ['all']]),
            new \Twig_SimpleFilter('oro_html_purify', [$this, 'htmlPurify']),
            new \Twig_SimpleFilter('oro_simple_html_purify', [$this, 'simpleHtmlPurify']),
            new \Twig_SimpleFilter('oro_html_sanitize', [$this, 'htmlSanitize'], ['is_safe' => ['html']]),
        ];
    }

    /**
     * @param string $string
     *
     * @return string
     */
    public function tagFilter($string)
    {
        return $this->htmlTagHelper->stripTags($string);
    }

    /**
     * Can be used before stripping html tags to remove content of <script> and <style> tags
     * so that what's left is text without some styling or scripting stuff which can be read
     * easilly.
     *
     * @param string $string
     *
     * @return string
     */
    public function simpleHtmlPurify($string)
    {
        return preg_replace('/<(style|script).*?>.*?<\/\1>/s', '', $string);
    }

    /**
     * Filter is intended to purify script, style etc tags and content of them
     *
     * @param string $string
     * @return string
     */
    public function htmlPurify($string)
    {
        return $this->htmlTagHelper->purify($string);
    }

    /**
     * @param string $string
     *
     * @return string
     */
    public function htmlSanitize($string)
    {
        return $this->htmlTagHelper->sanitize($string);
    }

    /**
     * {@inheritDoc}
     */
    public function getName()
    {
        return 'oro_ui.html_tag';
    }
}
