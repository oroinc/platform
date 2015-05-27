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
        ];
    }

    /**
     * @param string $string
     *
     * @return string
     */
    public function tagFilter($string)
    {
        return $this->htmlTagHelper->getStripped($string);
    }

    /**
     * Filter is intended to purify script, style etc tags and content of them
     *
     * @param string $string
     * @return string
     */
    public function htmlPurify($string)
    {
        return $this->htmlTagHelper->getPurify($string);
    }

    /**
     * {@inheritDoc}
     */
    public function getName()
    {
        return 'oro_ui.html_tag';
    }
}
