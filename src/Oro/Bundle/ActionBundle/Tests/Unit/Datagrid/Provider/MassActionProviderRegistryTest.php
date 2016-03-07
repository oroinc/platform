<?php

namespace Oro\Bundle\ActionBundle\Tests\Unit\Datagrid\Provider;

use Oro\Bundle\ActionBundle\Datagrid\Provider\MassActionProviderInterface;
use Oro\Bundle\ActionBundle\Datagrid\Provider\MassActionProviderRegistry;

class MassActionProviderRegistryTest extends \PHPUnit_Framework_TestCase
{
    /** @var MassActionProviderRegistry */
    protected $registry;

    protected function setUp()
    {
        $this->registry = new MassActionProviderRegistry();
    }

    protected function tearDown()
    {
        unset($this->registry);
    }

    public function testAddAndGetProvider()
    {
        $this->assertAttributeEmpty('providers', $this->registry);

        $provider = $this->getProvider();

        $this->registry->addProvider('test_provider', $provider);

        $this->assertAttributeCount(1, 'providers', $this->registry);
        $this->assertSame($provider, $this->registry->getProvider('test_provider'));
    }

    /**
     * @return MassActionProviderInterface
     */
    protected function getProvider()
    {
        return $this->getMock('Oro\Bundle\ActionBundle\Datagrid\Provider\MassActionProviderInterface');
    }
}
