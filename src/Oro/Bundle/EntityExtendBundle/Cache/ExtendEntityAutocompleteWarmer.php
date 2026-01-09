<?php

namespace Oro\Bundle\EntityExtendBundle\Cache;

use Oro\Bundle\EntityExtendBundle\Tools\ExtendAutocompleteGenerator;
use Symfony\Component\HttpKernel\CacheWarmer\CacheWarmer;

/**
 * Extend entity autocomplete warmer.
 */
class ExtendEntityAutocompleteWarmer extends CacheWarmer
{
    public function __construct(protected ExtendAutocompleteGenerator $extendAutocompleteGenerator)
    {
    }

    #[\Override]
    public function warmUp($cacheDir, ?string $buildDir = null): array
    {
        $this->extendAutocompleteGenerator->generate();
        return [];
    }

    #[\Override]
    public function isOptional(): bool
    {
        return true;
    }
}
