<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Provider;

use Oro\Bundle\EmailBundle\Provider\VariablesProvider;

class VariablesProviderTest extends \PHPUnit_Framework_TestCase
{
    /** @var VariablesProvider */
    protected $provider;

    protected function setUp()
    {
        $this->provider = new VariablesProvider();
    }

    public function testGetSystemVariableDefinitions()
    {
        $provider1 = $this->getMock('Oro\Bundle\EmailBundle\Provider\SystemVariablesProviderInterface');
        $provider1->expects($this->once())
            ->method('getVariableDefinitions')
            ->will(
                $this->returnValue(
                    ['var1' => ['type' => 'string', 'label' => 'var1']]
                )
            );

        $provider2 = $this->getMock('Oro\Bundle\EmailBundle\Provider\SystemVariablesProviderInterface');
        $provider2->expects($this->once())
            ->method('getVariableDefinitions')
            ->will(
                $this->returnValue(
                    ['var2' => ['type' => 'string', 'label' => 'var2']]
                )
            );

        $this->provider->addSystemVariablesProvider($provider1);
        $this->provider->addSystemVariablesProvider($provider2);

        $result = $this->provider->getSystemVariableDefinitions();
        $this->assertEquals(
            [
                'var1' => ['type' => 'string', 'label' => 'var1'],
                'var2' => ['type' => 'string', 'label' => 'var2']
            ],
            $result
        );
    }

    public function testGetEntityVariableDefinitions()
    {
        $entityClass = 'TestEntity';

        $provider1 = $this->getMock('Oro\Bundle\EmailBundle\Provider\EntityVariablesProviderInterface');
        $provider1->expects($this->once())
            ->method('getVariableDefinitions')
            ->with($entityClass)
            ->will(
                $this->returnValue(
                    ['var1' => ['type' => 'string', 'label' => 'var1']]
                )
            );

        $provider2 = $this->getMock('Oro\Bundle\EmailBundle\Provider\EntityVariablesProviderInterface');
        $provider2->expects($this->once())
            ->method('getVariableDefinitions')
            ->with($entityClass)
            ->will(
                $this->returnValue(
                    ['var2' => ['type' => 'string', 'label' => 'var2']]
                )
            );

        $this->provider->addEntityVariablesProvider($provider1);
        $this->provider->addEntityVariablesProvider($provider2);

        $result = $this->provider->getEntityVariableDefinitions($entityClass);
        $this->assertEquals(
            [
                'var1' => ['type' => 'string', 'label' => 'var1'],
                'var2' => ['type' => 'string', 'label' => 'var2']
            ],
            $result
        );
    }

    public function testGetSystemVariableValues()
    {
        $provider1 = $this->getMock('Oro\Bundle\EmailBundle\Provider\SystemVariablesProviderInterface');
        $provider1->expects($this->once())
            ->method('getVariableValues')
            ->will(
                $this->returnValue(
                    ['var1' => 'val1']
                )
            );

        $provider2 = $this->getMock('Oro\Bundle\EmailBundle\Provider\SystemVariablesProviderInterface');
        $provider2->expects($this->once())
            ->method('getVariableValues')
            ->will(
                $this->returnValue(
                    ['var2' => 'val2']
                )
            );

        $this->provider->addSystemVariablesProvider($provider1);
        $this->provider->addSystemVariablesProvider($provider2);

        $result = $this->provider->getSystemVariableValues();
        $this->assertEquals(
            [
                'var1' => 'val1',
                'var2' => 'val2'
            ],
            $result
        );
    }

    public function testGetEntityVariableGetters()
    {
        $entityClass = 'TestEntity';

        $provider1 = $this->getMock('Oro\Bundle\EmailBundle\Provider\EntityVariablesProviderInterface');
        $provider1->expects($this->once())
            ->method('getVariableGetters')
            ->with($entityClass)
            ->will(
                $this->returnValue(
                    ['var1' => 'getVar1']
                )
            );

        $provider2 = $this->getMock('Oro\Bundle\EmailBundle\Provider\EntityVariablesProviderInterface');
        $provider2->expects($this->once())
            ->method('getVariableGetters')
            ->with($entityClass)
            ->will(
                $this->returnValue(
                    ['var2' => 'getVar2']
                )
            );

        $this->provider->addEntityVariablesProvider($provider1);
        $this->provider->addEntityVariablesProvider($provider2);

        $result = $this->provider->getEntityVariableGetters($entityClass);
        $this->assertEquals(
            [
                'var1' => 'getVar1',
                'var2' => 'getVar2'
            ],
            $result
        );
    }
}
