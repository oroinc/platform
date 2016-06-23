<?php

namespace Oro\Bundle\RequireJSBundle\Tests\Unit\Provider;

use Oro\Bundle\RequireJSBundle\Provider\ChainConfigProvider;
use Oro\Bundle\RequireJSBundle\Provider\ConfigProviderInterface;

class ChainConfigProviderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ChainConfigProvider
     */
    protected $chainProvider;

    protected function setUp()
    {
        $this->chainProvider = new ChainConfigProvider();
        $this->chainProvider->addProvider($this->getMockConfigProviderInterface());
        $this->chainProvider->addProvider($this->getMockConfigProviderInterface());
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

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|ConfigProviderInterface
     */
    protected function getMockConfigProviderInterface()
    {
        return $this->getMock('Oro\Bundle\RequireJSBundle\Provider\ConfigProviderInterface');
    }
}
