<?php

namespace Oro\Bundle\TranslationBundle\Cache;

/**
 * The default implementation of the translation cache rebuild handler.
 */
class RebuildTranslationCacheHandler implements RebuildTranslationCacheHandlerInterface
{
    /** @var RebuildTranslationCacheProcessor */
    private $rebuildTranslationCacheProcessor;

    public function __construct(RebuildTranslationCacheProcessor $rebuildTranslationCacheProcessor)
    {
        $this->rebuildTranslationCacheProcessor = $rebuildTranslationCacheProcessor;
    }

    /**
     * {@inheritDoc}
     */
    public function rebuildCache(): RebuildTranslationCacheResult
    {
        return new RebuildTranslationCacheResult($this->rebuildTranslationCacheProcessor->rebuildCache());
    }
}
