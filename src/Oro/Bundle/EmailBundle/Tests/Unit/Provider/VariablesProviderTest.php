<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Provider;

use Oro\Bundle\EmailBundle\Provider\VariablesProvider;

class VariablesProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var VariablesProvider */
    protected $provider;

    protected function setUp()
    {
        $this->provider = new VariablesProvider();
    }

    public function testGetSystemVariableDefinitions()
    {
        $provider1 = $this->createMock('Oro\Bundle\EmailBundle\Provider\SystemVariablesProviderInterface');
        $provider1->expects($this->once())
            ->method('getVariableDefinitions')
            ->will(
                $this->returnValue(
                    [
                        'var1' => ['type' => 'string', 'label' => 'var1'],
                        'var3' => ['type' => 'string', 'label' => 'var3']
                    ]
                )
            );

        $provider2 = $this->createMock('Oro\Bundle\EmailBundle\Provider\SystemVariablesProviderInterface');
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
        $this->assertSame(
            [
                'var1' => ['type' => 'string', 'label' => 'var1'],
                'var2' => ['type' => 'string', 'label' => 'var2'],
                'var3' => ['type' => 'string', 'label' => 'var3'],
            ],
            $result
        );
    }

    public function testGetEntityVariableDefinitionsForOneEntity()
    {
        $entityClass = 'TestEntity';

        $provider1 = $this->createMock('Oro\Bundle\EmailBundle\Provider\EntityVariablesProviderInterface');
        $provider1->expects($this->once())
            ->method('getVariableDefinitions')
            ->with($entityClass)
            ->will(
                $this->returnValue(
                    [
                        'var1' => ['type' => 'string', 'label' => 'var1'],
                        'var3' => ['type' => 'string', 'label' => 'var3'],
                    ]
                )
            );

        $provider2 = $this->createMock('Oro\Bundle\EmailBundle\Provider\EntityVariablesProviderInterface');
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
        $this->assertSame(
            [
                'var1' => ['type' => 'string', 'label' => 'var1'],
                'var2' => ['type' => 'string', 'label' => 'var2'],
                'var3' => ['type' => 'string', 'label' => 'var3'],
            ],
            $result
        );
    }

    public function testGetEntityVariableDefinitionsForAllEntities()
    {
        $entity1Class = 'TestEntity1';
        $entity2Class = 'TestEntity2';

        $provider1 = $this->createMock('Oro\Bundle\EmailBundle\Provider\EntityVariablesProviderInterface');
        $provider1->expects($this->once())
            ->method('getVariableDefinitions')
            ->with(null)
            ->will(
                $this->returnValue(
                    [
                        $entity1Class => [
                            'var1' => ['type' => 'string', 'label' => 'var1'],
                            'var3' => ['type' => 'string', 'label' => 'var3'],
                        ]
                    ]
                )
            );

        $provider2 = $this->createMock('Oro\Bundle\EmailBundle\Provider\EntityVariablesProviderInterface');
        $provider2->expects($this->once())
            ->method('getVariableDefinitions')
            ->with(null)
            ->will(
                $this->returnValue(
                    [$entity1Class => ['var2' => ['type' => 'string', 'label' => 'var2']]]
                )
            );

        $provider3 = $this->createMock('Oro\Bundle\EmailBundle\Provider\EntityVariablesProviderInterface');
        $provider3->expects($this->once())
            ->method('getVariableDefinitions')
            ->with(null)
            ->will(
                $this->returnValue(
                    [$entity2Class => ['var1' => ['type' => 'string', 'label' => 'var1']]]
                )
            );

        $this->provider->addEntityVariablesProvider($provider1);
        $this->provider->addEntityVariablesProvider($provider2);
        $this->provider->addEntityVariablesProvider($provider3);

        $result = $this->provider->getEntityVariableDefinitions();
        $this->assertSame(
            [
                $entity1Class => [
                    'var1' => ['type' => 'string', 'label' => 'var1'],
                    'var2' => ['type' => 'string', 'label' => 'var2'],
                    'var3' => ['type' => 'string', 'label' => 'var3'],
                ],
                $entity2Class => [
                    'var1' => ['type' => 'string', 'label' => 'var1'],
                ],
            ],
            $result
        );
    }

    public function testGetSystemVariableValues()
    {
        $provider1 = $this->createMock('Oro\Bundle\EmailBundle\Provider\SystemVariablesProviderInterface');
        $provider1->expects($this->once())
            ->method('getVariableValues')
            ->will(
                $this->returnValue(
                    ['var1' => 'val1']
                )
            );

        $provider2 = $this->createMock('Oro\Bundle\EmailBundle\Provider\SystemVariablesProviderInterface');
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

    public function testGetEntityVariableGettersForOneEntity()
    {
        $entityClass = 'TestEntity';

        $provider1 = $this->createMock('Oro\Bundle\EmailBundle\Provider\EntityVariablesProviderInterface');
        $provider1->expects($this->once())
            ->method('getVariableGetters')
            ->with($entityClass)
            ->will(
                $this->returnValue(
                    ['var1' => 'getVar1']
                )
            );

        $provider2 = $this->createMock('Oro\Bundle\EmailBundle\Provider\EntityVariablesProviderInterface');
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

    public function testGetEntityVariableGettersForAllEntities()
    {
        $entity1Class = 'TestEntity1';
        $entity2Class = 'TestEntity2';

        $provider1 = $this->createMock('Oro\Bundle\EmailBundle\Provider\EntityVariablesProviderInterface');
        $provider1->expects($this->once())
            ->method('getVariableGetters')
            ->with(null)
            ->will(
                $this->returnValue(
                    [$entity1Class => ['var1' => 'getVar1']]
                )
            );

        $provider2 = $this->createMock('Oro\Bundle\EmailBundle\Provider\EntityVariablesProviderInterface');
        $provider2->expects($this->once())
            ->method('getVariableGetters')
            ->with(null)
            ->will(
                $this->returnValue(
                    [$entity1Class => ['var2' => 'getVar2']]
                )
            );

        $provider3 = $this->createMock('Oro\Bundle\EmailBundle\Provider\EntityVariablesProviderInterface');
        $provider3->expects($this->once())
            ->method('getVariableGetters')
            ->with(null)
            ->will(
                $this->returnValue(
                    [$entity2Class => ['var1' => 'getVar1']]
                )
            );

        $this->provider->addEntityVariablesProvider($provider1);
        $this->provider->addEntityVariablesProvider($provider2);
        $this->provider->addEntityVariablesProvider($provider3);

        $result = $this->provider->getEntityVariableGetters();
        $this->assertEquals(
            [
                $entity1Class => [
                    'var1' => 'getVar1',
                    'var2' => 'getVar2',
                ],
                $entity2Class => [
                    'var1' => 'getVar1',
                ],
            ],
            $result
        );
    }
}
