<?php

namespace Oro\Component\Config\Tests\Unit\Fixtures;

use Oro\Component\Config\Cache\PhpArrayConfigProvider;
use Oro\Component\Config\ResourcesContainerInterface;
use Symfony\Component\Config\ConfigCacheFactoryInterface;

class PhpArrayConfigProviderStub extends PhpArrayConfigProvider
{
    /** @var callable */
    private $loadConfigCallback;

    /**
     * @param string                      $cacheFile
     * @param ConfigCacheFactoryInterface $configCacheFactory
     * @param callable                    $loadConfigCallback
     */
    public function __construct(
        string $cacheFile,
        ConfigCacheFactoryInterface $configCacheFactory,
        callable $loadConfigCallback
    ) {
        parent::__construct($cacheFile, $configCacheFactory);
        $this->loadConfigCallback = $loadConfigCallback;
    }

    /**
     * @return mixed
     */
    public function getConfig()
    {
        return $this->doGetConfig();
    }

    /**
     * {@inheritdoc}
     */
    protected function doLoadConfig(ResourcesContainerInterface $resourcesContainer)
    {
        return call_user_func($this->loadConfigCallback, $resourcesContainer);
    }
}
