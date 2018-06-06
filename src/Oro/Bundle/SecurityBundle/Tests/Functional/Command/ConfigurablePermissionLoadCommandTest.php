<?php

namespace Oro\Bundle\SecurityBundle\Tests\Functional\Command;

use Oro\Bundle\SecurityBundle\Acl\Permission\ConfigurablePermissionProvider;
use Oro\Bundle\SecurityBundle\Command\LoadConfigurablePermissionCommand;
use Oro\Bundle\SecurityBundle\Configuration\ConfigurablePermissionConfigurationProvider;
use Oro\Bundle\SecurityBundle\Tests\Functional\Command\Stub\TestBundle1\TestBundle1;
use Oro\Bundle\SecurityBundle\Tests\Functional\Command\Stub\TestBundle2\TestBundle2;
use Oro\Bundle\SecurityBundle\Tests\Functional\Command\Stub\TestBundleIncorrect\TestBundleIncorrect;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Component\Config\CumulativeResourceManager;
use Symfony\Component\HttpKernel\Bundle\BundleInterface;

class ConfigurablePermissionLoadCommandTest extends WebTestCase
{
    /** @var ConfigurablePermissionConfigurationProvider */
    protected $provider;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->initClient();

        $this->provider = $this->getContainer()
            ->get('oro_security.configuration.provider.configurable_permission_configuration');
    }

    public function testExecute()
    {
        $this->loadBundles([new TestBundle1(), new TestBundle2()]);

        $result = $this->runCommand(LoadConfigurablePermissionCommand::NAME, ['--no-ansi']);
        $this->assertContains('All configurable permissions successfully loaded into cache', $result);

        $cache = $this->getContainer()->get('oro_security.cache.provider.configurable_permission');
        $cacheData = $cache->fetch(ConfigurablePermissionProvider::CACHE_ID);

        $this->assertArraySubset(
            [
                'test_configurable_permissions1' => ['default' => true]
            ],
            $cacheData
        );

        $this->assertArraySubset(
            [
                'test_configurable_permissions3' => [
                    'entities' => [
                        'Oro\Bundle\TestFrameworkBundle\Entity\Item' => ['CREATE' => false]
                    ]
                ]
            ],
            $cacheData
        );
    }

    public function testExecuteWhenInvalidConfiguration()
    {
        $this->loadBundles([new TestBundleIncorrect(), new TestBundle1()]);

        $result = $this->runCommand(LoadConfigurablePermissionCommand::NAME, ['--no-ansi']);
        $this->assertContains('In AbstractPermissionsConfigurationProvider.php', $result);
        $this->assertContains('Can\'t parse permission configuration', $result);
    }

    /**
     * @param BundleInterface[] $bundleClasses
     */
    protected function loadBundles(array $bundleClasses)
    {
        $bundles = [];
        foreach ($bundleClasses as $bundle) {
            $bundles[$bundle->getName()] = get_class($bundle);
        }

        CumulativeResourceManager::getInstance()->clear()->setBundles($bundles);

        $reflection = new \ReflectionClass($this->provider);
        $reflectionProperty = $reflection->getProperty('kernelBundles');
        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue($this->provider, $bundles);
    }
}
