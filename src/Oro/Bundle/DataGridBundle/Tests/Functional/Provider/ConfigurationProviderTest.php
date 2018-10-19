<?php

namespace Oro\Bundle\DataGridBundle\Tests\Functional\Provider;

use Oro\Bundle\CacheBundle\Provider\FilesystemCache;
use Oro\Bundle\DataGridBundle\Provider\ConfigurationProvider;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class ConfigurationProviderTest extends WebTestCase
{
    protected function setUp()
    {
        $this->initClient([], $this->generateBasicAuthHeader());
    }

    public function testGridConfigurationReloadedIfRemovedFromCache()
    {
        $dataGriName = 'audit-grid';

        $resolver = $this->getContainer()->get('oro_datagrid.provider.resolver');
        $cache = $this->getCache();

        // Get configuration to be sure that cache is filled with data
        $provider = new ConfigurationProvider($resolver, $cache);
        $config = $provider->getRawConfiguration($dataGriName);
        $this->assertNotEmpty($config);

        // Delete single grid config from cache
        $this->assertTrue($cache->contains($dataGriName));
        $cache->delete($dataGriName);
        $this->assertFalse($cache->contains($dataGriName));

        // Create new provider instance to bypass caching in local variable and force config loading logic
        $newProvider = new ConfigurationProvider($resolver, $cache);
        // Try to load configuration again and check that it is loaded correctly
        $reloadedConfig = $newProvider->getRawConfiguration($dataGriName);
        $this->assertTrue($cache->contains($dataGriName));
        $this->assertEquals($config, $reloadedConfig);
    }

    /**
     * @return FilesystemCache
     */
    private function getCache(): FilesystemCache
    {
        $cache = new FilesystemCache(
            $this->getContainer()->getParameter('kernel.cache_dir') . DIRECTORY_SEPARATOR . 'oro_test'
        );
        $cache->setNamespace('oro_datagrid_configuration');

        return $cache;
    }
}
