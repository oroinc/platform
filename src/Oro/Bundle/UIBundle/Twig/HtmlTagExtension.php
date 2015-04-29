<?php

namespace Oro\Bundle\UIBundle\Twig;

use Oro\Bundle\FormBundle\Form\DataTransformer\SanitizeHTMLTransformer;
use Oro\Bundle\FormBundle\Provider\HtmlTagProvider;

class HtmlTagExtension extends \Twig_Extension
{
    /**
     * @var HtmlTagProvider
     */
    protected $htmlTagProvider;

    /**
     * @var string
     */
    protected $cacheDir;

    /**
     * @param HtmlTagProvider $htmlTagProvider
     * @param string $cacheDir
     */
    public function __construct(HtmlTagProvider $htmlTagProvider, $cacheDir = null)
    {
        $this->htmlTagProvider = $htmlTagProvider;
        $this->cacheDir = $cacheDir;
    }

    /**
     * {@inheritDoc}
     */
    public function getFilters()
    {
        return [
            new \Twig_SimpleFilter('oro_tag_filter', [$this, 'tagFilter'], ['is_safe' => ['all']]),
            new \Twig_SimpleFilter('oro_html_purify', [$this, 'htmlPurify']),
        ];
    }

    /**
     * @param string $string
     *
     * @return string
     */
    public function tagFilter($string)
    {
        return strip_tags($string, $this->htmlTagProvider->getAllowedTags());
    }

    /**
     * Filter is inteded to purify script, style etc tags and content of them
     *
     * @param string $string
     * @return string
     */
    public function htmlPurify($string)
    {
        $transformer = new SanitizeHTMLTransformer(null, $this->cacheDir);
        return $transformer->transform($string);
    }

    /**
     * {@inheritDoc}
     */
    public function getName()
    {
        return 'oro_ui.html_tag';
    }
}
