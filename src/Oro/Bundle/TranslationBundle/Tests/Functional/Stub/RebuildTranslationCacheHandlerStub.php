<?php

namespace Oro\Bundle\TranslationBundle\Tests\Functional\Stub;

use Oro\Bundle\TranslationBundle\Cache\RebuildTranslationCacheHandlerInterface;
use Oro\Bundle\TranslationBundle\Cache\RebuildTranslationCacheResult;

class RebuildTranslationCacheHandlerStub implements RebuildTranslationCacheHandlerInterface
{
    /** @var RebuildTranslationCacheHandlerInterface */
    private $handler;

    /** @var callable|null */
    private $rebuildCacheCallback;

    /**
     * @param RebuildTranslationCacheHandlerInterface $handler
     */
    public function __construct(RebuildTranslationCacheHandlerInterface $handler)
    {
        $this->handler = $handler;
    }

    /**
     * @param callable|null $rebuildCache
     */
    public function setRebuildCache(?callable $rebuildCache): void
    {
        $this->rebuildCacheCallback = $rebuildCache;
    }

    /**
     * {@inheritDoc}
     */
    public function rebuildCache(): RebuildTranslationCacheResult
    {
        if ($this->rebuildCacheCallback) {
            return ($this->rebuildCacheCallback)();
        }

        return $this->handler->rebuildCache();
    }
}
