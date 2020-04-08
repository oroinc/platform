<?php

namespace Oro\Bundle\CacheBundle\Provider;

/**
 * The memory cache provider aware trait which uses the NullMemoryCacheProvider as a default substitute
 * of the MemoryCacheProvider.
 */
trait MemoryCacheProviderAwareTrait
{
    /** @var MemoryCacheProviderInterface|null */
    private $memoryCacheProvider;

    /**
     * @return MemoryCacheProviderInterface
     */
    protected function getMemoryCacheProvider(): MemoryCacheProviderInterface
    {
        if (!$this->memoryCacheProvider) {
            $this->memoryCacheProvider = new NullMemoryCacheProvider();
        }

        return $this->memoryCacheProvider;
    }

    /**
     * @param MemoryCacheProviderInterface|null $memoryCacheProvider
     */
    public function setMemoryCacheProvider(?MemoryCacheProviderInterface $memoryCacheProvider): void
    {
        $this->memoryCacheProvider = $memoryCacheProvider;
    }
}
