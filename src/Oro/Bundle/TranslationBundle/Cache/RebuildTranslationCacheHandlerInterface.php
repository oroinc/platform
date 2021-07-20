<?php

namespace Oro\Bundle\TranslationBundle\Cache;

/**
 * Represents a service that is used to rebuild the translation cache.
 */
interface RebuildTranslationCacheHandlerInterface
{
    /**
     * Rebuilds the translation cache.
     */
    public function rebuildCache(): RebuildTranslationCacheResult;
}
