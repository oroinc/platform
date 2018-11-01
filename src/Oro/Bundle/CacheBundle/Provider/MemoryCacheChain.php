<?php

namespace Oro\Bundle\CacheBundle\Provider;

use Doctrine\Common\Cache\CacheProvider;
use Doctrine\Common\Cache\ChainCache;
use Oro\Component\PhpUtils\ReflectionUtil;

/**
 * The cache provider that allows to chain multiple cache providers
 * and adds a memory cache as the first cache provider.
 * Also, this providers decreases the number of "contains" operations to the cache providers,
 * it is possible because the "fetch" operation returns FALSE
 * if the requested entry does not exist in the cache.
 */
class MemoryCacheChain extends ChainCache
{
    /** @var \ReflectionProperty */
    private $cacheProvidersProperty;

    /** @var int|null */
    private $memoryCacheKey;

    /**
     * {@inheritdoc}
     */
    public function __construct($cacheProviders = [])
    {
        if (PHP_SAPI !== 'cli') {
            array_unshift($cacheProviders, new ArrayCache());
            $this->memoryCacheKey = 0;
        }

        parent::__construct($cacheProviders);

        $this->cacheProvidersProperty = ReflectionUtil::getProperty(new \ReflectionClass($this), 'cacheProviders');
        $this->cacheProvidersProperty->setAccessible(true);
    }

    /**
     * {@inheritdoc}
     */
    protected function doFetch($id)
    {
        /** @var CacheProvider[] $cacheProviders */
        $cacheProviders = $this->cacheProvidersProperty->getValue($this);
        foreach ($cacheProviders as $key => $cacheProvider) {
            $value = $cacheProvider->doFetch($id);
            if (false !== $value) {
                $this->addValueToPreviousCaches($key, $cacheProviders, $id, $value);

                return $value;
            }

            if (null !== $this->memoryCacheKey) {
                if ($this->memoryCacheKey === $key) {
                    if ($cacheProvider->doContains($id)) {
                        return $value;
                    }
                } elseif ($cacheProvider->doContains($id)) {
                    $this->addValueToPreviousCaches($key, $cacheProviders, $id, $value);

                    return $value;
                }
            }
        }

        return false;
    }

    /**
     * @param int             $currentCacheProviderKey
     * @param CacheProvider[] $cacheProviders
     * @param string          $id
     * @param mixed           $value
     */
    private function addValueToPreviousCaches($currentCacheProviderKey, $cacheProviders, $id, $value)
    {
        for ($subKey = $currentCacheProviderKey - 1; $subKey >= 0; $subKey--) {
            $cacheProviders[$subKey]->doSave($id, $value);
        }
    }
}
