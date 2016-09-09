<?php

namespace Oro\Bundle\RequireJSBundle\Tests\Unit\Provider;

use Oro\Bundle\RequireJSBundle\Manager\ConfigProviderManager;
use Oro\Bundle\RequireJSBundle\Provider\ConfigProviderInterface;

class ConfigProviderManagerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ConfigProviderManager
     */
    protected $chainProvider;

    protected function setUp()
    {
        $this->chainProvider = new ConfigProviderManager();
        $this->chainProvider->addProvider($this->getMockConfigProviderInterface(), 'oro_requirejs_config_provider1');
        $this->chainProvider->addProvider($this->getMockConfigProviderInterface(), 'oro_requirejs_config_provider2');
    }

    public function testGetProviders()
    {
        $configProviders = $this->chainProvider->getProviders();
        $this->assertCount(2, $configProviders);
        $this->assertInstanceOf(
            'Oro\Bundle\RequireJSBundle\Provider\ConfigProviderInterface',
            current($configProviders)
        );
    }

    public function testGetProvider()
    {
        $configProvider = $this->chainProvider->getProvider('oro_requirejs_config_provider1');
        $this->assertInstanceOf(
            'Oro\Bundle\RequireJSBundle\Provider\ConfigProviderInterface',
            $configProvider
        );
    }

    public function testGetProviderNotExist()
    {
        $configProvider = $this->chainProvider->getProvider('oro_requirejs_config_provider3');
        $this->assertNull($configProvider);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|ConfigProviderInterface
     */
    protected function getMockConfigProviderInterface()
    {
        return $this->getMock('Oro\Bundle\RequireJSBundle\Provider\ConfigProviderInterface');
    }
}
