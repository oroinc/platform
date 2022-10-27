<?php

namespace Oro\Component\Config\Tests\Unit\Fixtures;

use Oro\Component\Config\Cache\PhpArrayConfigProvider;
use Oro\Component\Config\ResourcesContainerInterface;

class PhpArrayConfigProviderStub extends PhpArrayConfigProvider
{
    /** @var callable */
    private $loadConfigCallback;

    public function __construct(string $cacheFile, bool $debug, callable $loadConfigCallback)
    {
        parent::__construct($cacheFile, $debug);
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
