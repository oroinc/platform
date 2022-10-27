<?php

namespace Oro\Bundle\EntityBundle\Tests\Unit\Twig\Sandbox;

use Oro\Bundle\EntityBundle\Twig\Sandbox\EntityVariablesProviderInterface;
use Oro\Bundle\EntityBundle\Twig\Sandbox\SystemVariablesProviderInterface;
use Oro\Bundle\EntityBundle\Twig\Sandbox\VariablesProvider;
use Symfony\Component\DependencyInjection\ServiceLocator;

class VariablesProviderTest extends \PHPUnit\Framework\TestCase
{
    public function testGetSystemVariableDefinitions()
    {
        $provider1 = $this->createMock(SystemVariablesProviderInterface::class);
        $provider1->expects($this->once())
            ->method('getVariableDefinitions')
            ->willReturn([
                'var1' => ['type' => 'string', 'label' => 'var1'],
                'var3' => ['type' => 'string', 'label' => 'var3']
            ]);

        $provider2 = $this->createMock(SystemVariablesProviderInterface::class);
        $provider2->expects($this->once())
            ->method('getVariableDefinitions')
            ->willReturn([
                'var2' => ['type' => 'string', 'label' => 'var2'],
                'var3' => ['label' => 'var3_updated']
            ]);

        $chainProvider = new VariablesProvider(
            new ServiceLocator([
                'provider1' => function () use ($provider1) {
                    return $provider1;
                },
                'provider2' => function () use ($provider2) {
                    return $provider2;
                }
            ]),
            ['provider1', 'provider2'],
            []
        );

        $result = $chainProvider->getSystemVariableDefinitions();
        $this->assertSame(
            [
                'var1' => ['type' => 'string', 'label' => 'var1'],
                'var2' => ['type' => 'string', 'label' => 'var2'],
                'var3' => ['type' => 'string', 'label' => 'var3_updated']
            ],
            $result
        );
    }

    public function testGetEntityVariableDefinitions()
    {
        $entity1Class = 'TestEntity1';
        $entity2Class = 'TestEntity2';

        $provider1 = $this->createMock(EntityVariablesProviderInterface::class);
        $provider1->expects($this->once())
            ->method('getVariableDefinitions')
            ->willReturn([
                $entity1Class => [
                    'var1' => ['type' => 'string', 'label' => 'var1'],
                    'var3' => ['type' => 'string', 'label' => 'var3'],
                    'a1'   => ['type' => 'string', 'label' => 'a1', 'params' => ['key' => 'val']]
                ]
            ]);

        $provider2 = $this->createMock(EntityVariablesProviderInterface::class);
        $provider2->expects($this->once())
            ->method('getVariableDefinitions')
            ->willReturn([
                $entity1Class => ['var2' => ['type' => 'string', 'label' => 'var2']]
            ]);

        $provider3 = $this->createMock(EntityVariablesProviderInterface::class);
        $provider3->expects($this->once())
            ->method('getVariableDefinitions')
            ->willReturn([
                $entity1Class => [
                    'var2' => ['type' => 'string', 'label' => 'var2'],
                    'var3' => ['label' => 'var3_updated'],
                    'a1'   => ['params' => ['key_updated' => 'val_updated']]
                ],
                $entity2Class => [
                    'var1' => ['type' => 'string', 'label' => 'var1']
                ]
            ]);

        $chainProvider = new VariablesProvider(
            new ServiceLocator([
                'provider1' => function () use ($provider1) {
                    return $provider1;
                },
                'provider2' => function () use ($provider2) {
                    return $provider2;
                },
                'provider3' => function () use ($provider3) {
                    return $provider3;
                }
            ]),
            [],
            ['provider1', 'provider2', 'provider3']
        );

        $result = $chainProvider->getEntityVariableDefinitions();
        $this->assertSame(
            [
                $entity1Class => [
                    'a1'   => ['type' => 'string', 'label' => 'a1', 'params' => ['key_updated' => 'val_updated']],
                    'var1' => ['type' => 'string', 'label' => 'var1'],
                    'var2' => ['type' => 'string', 'label' => 'var2'],
                    'var3' => ['type' => 'string', 'label' => 'var3_updated']
                ],
                $entity2Class => [
                    'var1' => ['type' => 'string', 'label' => 'var1']
                ]
            ],
            $result
        );
    }

    public function testGetSystemVariableValues()
    {
        $provider1 = $this->createMock(SystemVariablesProviderInterface::class);
        $provider1->expects($this->once())
            ->method('getVariableValues')
            ->willReturn([
                'var1' => 'val1',
                'var2' => 'val2'
            ]);

        $provider2 = $this->createMock(SystemVariablesProviderInterface::class);
        $provider2->expects($this->once())
            ->method('getVariableValues')
            ->willReturn([
                'var2' => 'val2_updated',
                'var3' => 'val3'
            ]);

        $chainProvider = new VariablesProvider(
            new ServiceLocator([
                'provider1' => function () use ($provider1) {
                    return $provider1;
                },
                'provider2' => function () use ($provider2) {
                    return $provider2;
                }
            ]),
            ['provider1', 'provider2'],
            []
        );

        $result = $chainProvider->getSystemVariableValues();
        $this->assertEquals(
            [
                'var1' => 'val1',
                'var2' => 'val2_updated',
                'var3' => 'val3'
            ],
            $result
        );
    }

    public function testGetEntityVariableProcessors()
    {
        $entityClass = 'TestEntity';

        $provider1 = $this->createMock(EntityVariablesProviderInterface::class);
        $provider1->expects($this->once())
            ->method('getVariableProcessors')
            ->with($entityClass)
            ->willReturn([
                'var1' => ['processor' => 'processor1'],
                'var3' => ['processor' => 'processor3', 'param3' => 'val3']
            ]);

        $provider2 = $this->createMock(EntityVariablesProviderInterface::class);
        $provider2->expects($this->once())
            ->method('getVariableProcessors')
            ->with($entityClass)
            ->willReturn([
                'var2' => ['processor' => 'processor2'],
                'var3' => ['processor' => 'processor3_updated'],
                'var4' => ['processor' => 'processor4', 'param4' => 'val4']
            ]);

        $chainProvider = new VariablesProvider(
            new ServiceLocator([
                'provider1' => function () use ($provider1) {
                    return $provider1;
                },
                'provider2' => function () use ($provider2) {
                    return $provider2;
                }
            ]),
            [],
            ['provider1', 'provider2']
        );

        $result = $chainProvider->getEntityVariableProcessors($entityClass);
        $this->assertSame(
            [
                'var1' => ['processor' => 'processor1'],
                'var3' => ['processor' => 'processor3_updated'],
                'var2' => ['processor' => 'processor2'],
                'var4' => ['processor' => 'processor4', 'param4' => 'val4']
            ],
            $result
        );
    }

    public function testGetEntityVariableGetters()
    {
        $entity1Class = 'TestEntity1';
        $entity2Class = 'TestEntity2';

        $provider1 = $this->createMock(EntityVariablesProviderInterface::class);
        $provider1->expects($this->once())
            ->method('getVariableGetters')
            ->willReturn([
                $entity1Class => ['var1' => 'getVar1']
            ]);

        $provider2 = $this->createMock(EntityVariablesProviderInterface::class);
        $provider2->expects($this->once())
            ->method('getVariableGetters')
            ->willReturn([
                $entity1Class => [
                    'var1'  => 'getVar1',
                    'var2'  => 'getVar2',
                    'var4'  => null,
                    'var10' => null,
                    'var11' => 'getVar11',
                    'var12' => 'getVar12',
                    'var13' => ['property_path' => null],
                    'var14' => ['property_path' => 'getVar14'],
                    'var15' => ['property_path' => null, 'default_formatter' => 'formatter15'],
                    'var16' => ['property_path' => 'getVar16', 'default_formatter' => 'formatter16']
                ]
            ]);

        $provider3 = $this->createMock(EntityVariablesProviderInterface::class);
        $provider3->expects($this->once())
            ->method('getVariableGetters')
            ->willReturn([
                $entity1Class => [
                    'var2'  => 'getVar2_updated',
                    'var3'  => 'getVar3',
                    'var10' => ['default_formatter' => 'formatter10_updated'],
                    'var11' => ['default_formatter' => 'formatter11_updated'],
                    'var12' => ['property_path' => 'getVar12_updated', 'default_formatter' => 'formatter12_updated'],
                    'var13' => ['default_formatter' => 'formatter13_updated'],
                    'var14' => ['default_formatter' => 'formatter14_updated'],
                    'var15' => ['default_formatter' => ['formatter15_updated']],
                    'var16' => ['default_formatter' => ['formatter16_updated']]
                ],
                $entity2Class => [
                    'var1' => 'getVar1'
                ]
            ]);

        $chainProvider = new VariablesProvider(
            new ServiceLocator([
                'provider1' => function () use ($provider1) {
                    return $provider1;
                },
                'provider2' => function () use ($provider2) {
                    return $provider2;
                },
                'provider3' => function () use ($provider3) {
                    return $provider3;
                }
            ]),
            [],
            ['provider1', 'provider2', 'provider3']
        );

        $result = $chainProvider->getEntityVariableGetters();
        $this->assertEquals(
            [
                $entity1Class => [
                    'var1'  => 'getVar1',
                    'var2'  => 'getVar2_updated',
                    'var3'  => 'getVar3',
                    'var4'  => null,
                    'var10' => ['property_path' => null, 'default_formatter' => 'formatter10_updated'],
                    'var11' => ['property_path' => 'getVar11', 'default_formatter' => 'formatter11_updated'],
                    'var12' => ['property_path' => 'getVar12_updated', 'default_formatter' => 'formatter12_updated'],
                    'var13' => ['property_path' => null, 'default_formatter' => 'formatter13_updated'],
                    'var14' => ['property_path' => 'getVar14', 'default_formatter' => 'formatter14_updated'],
                    'var15' => ['property_path' => null, 'default_formatter' => ['formatter15_updated']],
                    'var16' => ['property_path' => 'getVar16', 'default_formatter' => ['formatter16_updated']]
                ],
                $entity2Class => [
                    'var1' => 'getVar1'
                ]
            ],
            $result
        );
    }
}
