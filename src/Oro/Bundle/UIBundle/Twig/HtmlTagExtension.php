<?php

namespace Oro\Bundle\UIBundle\Twig;

use Oro\Bundle\FormBundle\Provider\HtmlTagProvider;

class HtmlTagExtension extends \Twig_Extension
{
    /**
     * @var HtmlTagProvider
     */
    protected $htmlTagProvider;

    /**
     * @param HtmlTagProvider $htmlTagProvider
     */
    public function __construct(HtmlTagProvider $htmlTagProvider)
    {
        $this->htmlTagProvider = $htmlTagProvider;
    }

    /**
     * {@inheritDoc}
     */
    public function getFilters()
    {
        return [
            new \Twig_SimpleFilter('oro_tag_filter', [$this, 'tagFilter'], ['is_safe' => ['all']]),
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
     * {@inheritDoc}
     */
    public function getName()
    {
        return 'oro_ui.html_tag';
    }
}
