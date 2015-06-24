<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\Owner\Metadata\Stub;

use Doctrine\Common\Cache\CacheProvider;

use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;

use Oro\Bundle\EntityConfigBundle\Config\ConfigInterface;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\SecurityBundle\Owner\Metadata\AbstractMetadataProvider;
use Oro\Bundle\SecurityBundle\Owner\Metadata\OwnershipMetadata;

class StubMetadataProvider extends AbstractMetadataProvider
{
    /**
     * @param ConfigProvider      $configProvider
     * @param CacheProvider|null  $cache
     */
    public function __construct(ConfigProvider $configProvider, CacheProvider $cache = null)
    {
        $this->configProvider = $configProvider;
        $this->cache = $cache;
        $this->noOwnershipMetadata = new OwnershipMetadata();
    }

    /**
     * {@inheritDoc}
     */
    protected function getOwnershipMetadata(ConfigInterface $config)
    {
        return new OwnershipMetadata();
    }

    /**
     * {@inheritDoc}
     */
    public function supports()
    {
        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function getBasicLevelClass()
    {
        return 'basicLevelClass';
    }

    /**
     * {@inheritDoc}
     */
    public function getLocalLevelClass($deep = false)
    {
        return 'localLevelClass';
    }

    /**
     * {@inheritDoc}
     */
    public function getGlobalLevelClass()
    {
        return 'globalLevelClass';
    }

    /**
     * {@inheritDoc}
     */
    public function getSystemLevelClass()
    {
        return 'systemLevelClass';
    }
}
