<?php

namespace Oro\Bundle\UIBundle\Cache;

use Oro\Bundle\FormBundle\Provider\HtmlTagProvider;
use Oro\Bundle\UIBundle\Tools\HtmlTagHelper;
use Symfony\Component\HttpKernel\CacheWarmer\CacheWarmerInterface;

/**
 * Warmer that warms the HTML purifiers config cache.
 */
class HTMLPurifierConfigCacheWarmer implements CacheWarmerInterface
{
    /** @var HtmlTagHelper */
    private $htmlTagHelper;

    /** @var HtmlTagProvider */
    private $htmlTagProvider;

    public function __construct(HtmlTagHelper $htmlTagHelper, HtmlTagProvider $htmlTagProvider)
    {
        $this->htmlTagHelper = $htmlTagHelper;
        $this->htmlTagProvider = $htmlTagProvider;
    }

    /**
     * {@inheritDoc}
     */
    public function warmUp($cacheDir)
    {
        foreach ($this->htmlTagProvider->getScopes() as $scope) {
            $this->htmlTagHelper->sanitize(
                '<p style="border: none">text</p><a href="http://localhost"></a>',
                $scope,
                false
            );
        }
        $this->htmlTagHelper->escape('<p style="border: none">text</p><a href="http://localhost"></a>');
    }

    /**
     * {@inheritDoc}
     */
    public function isOptional()
    {
        return false;
    }
}
