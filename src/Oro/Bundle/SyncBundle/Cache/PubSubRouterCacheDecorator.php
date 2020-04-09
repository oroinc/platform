<?php

namespace Oro\Bundle\SyncBundle\Cache;

use Doctrine\Common\Cache\Cache;

/**
 * Caching decorator which disables caching when debug is on.
 */
class PubSubRouterCacheDecorator implements Cache
{
    /** @var Cache */
    private $innerCache;

    /** @var bool */
    private $isDebug;

    /**
     * @param Cache $innerCache
     * @param bool $isDebug
     */
    public function __construct(Cache $innerCache, bool $isDebug)
    {
        $this->innerCache = $innerCache;
        $this->isDebug = $isDebug;
    }

    /**
     * {@inheritdoc}
     */
    public function fetch($id)
    {
        if ($this->isDebug) {
            return false;
        }

        return $this->innerCache->fetch($id);
    }

    /**
     * {@inheritdoc}
     */
    public function contains($id): bool
    {
        if ($this->isDebug) {
            return false;
        }

        return $this->innerCache->contains($id);
    }

    /**
     * {@inheritdoc}
     */
    public function save($id, $data, $lifeTime = 0): bool
    {
        if ($this->isDebug) {
            return true;
        }

        return $this->innerCache->save($id, $data, $lifeTime);
    }

    /**
     * {@inheritdoc}
     */
    public function delete($id): bool
    {
        return $this->innerCache->delete($id);
    }

    /**
     * {@inheritdoc}
     */
    public function getStats(): ?array
    {
        return $this->innerCache->getStats();
    }
}
