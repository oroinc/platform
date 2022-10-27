<?php

namespace Oro\Bundle\TestFrameworkBundle\Provider;

use Oro\Component\Config\Cache\PhpArrayConfigProvider;
use Oro\Component\Config\Cache\PhpConfigCacheAccessor;
use Oro\Component\PhpUtils\ReflectionUtil;
use Symfony\Component\Config\ResourceCheckerConfigCache;

/**
 * Allows to change cached data created by an instance PhpArrayConfigProvider
 * to be able to create functional and Behat tests.
 */
class PhpArrayConfigCacheModifier
{
    /** @var PhpArrayConfigProvider */
    private $provider;

    public function __construct(PhpArrayConfigProvider $provider)
    {
        $this->provider = $provider;
    }

    public function resetCache()
    {
        $this->provider->warmUpCache();
    }

    public function updateCache(array $configuration)
    {
        $cacheFile = $this->getProviderPrivateProperty('cacheFile')->getValue($this->provider);

        $cacheAccessor = new PhpConfigCacheAccessor();
        $cacheAccessor->save(new ResourceCheckerConfigCache($cacheFile), $configuration);

        $this->getProviderPrivateProperty('config')->setValue($this->provider, null);
    }

    private function getProviderPrivateProperty(string $propertyName): \ReflectionProperty
    {
        $property = ReflectionUtil::getProperty(new \ReflectionClass($this->provider), $propertyName);
        $property->setAccessible(true);

        return $property;
    }
}
