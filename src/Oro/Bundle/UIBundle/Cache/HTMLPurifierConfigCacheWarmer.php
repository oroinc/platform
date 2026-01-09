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

    #[\Override]
    public function warmUp($cacheDir, ?string $buildDir = null): array
    {
        foreach ($this->htmlTagProvider->getScopes() as $scope) {
            $this->htmlTagHelper->sanitize(
                '<p style="border: none">text</p><a href="http://localhost"></a>',
                $scope,
                false
            );
        }
        $this->htmlTagHelper->escape('<p style="border: none">text</p><a href="http://localhost"></a>');
        return [];
    }

    #[\Override]
    public function isOptional(): bool
    {
        return false;
    }
}
