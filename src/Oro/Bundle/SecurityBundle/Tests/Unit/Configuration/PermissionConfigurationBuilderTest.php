<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\Configuration;

use Doctrine\Common\Collections\ArrayCollection;

use Oro\Bundle\SecurityBundle\Configuration\PermissionConfigurationBuilder;
use Oro\Bundle\SecurityBundle\Entity\Permission;
use Oro\Bundle\SecurityBundle\Entity\PermissionEntity;

class PermissionConfigurationBuilderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var PermissionConfigurationBuilder
     */
    protected $builder;

    protected function setUp()
    {
        $repository = $this->getMockBuilder('Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->getMock();

        /** @var \PHPUnit_Framework_MockObject_MockObject */
        $doctrine = $this->getMockBuilder('Doctrine\Common\Persistence\ManagerRegistry')
            ->disableOriginalConstructor()
            ->getMock();

        /** @var \PHPUnit_Framework_MockObject_MockObject */
        $em = $this->getMockBuilder('\Doctrine\Common\Persistence\ObjectManager')
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->any())
        ->method('getRepository')
        ->with('OroSecurityBundle:PermissionEntity')
        ->willReturn($repository);


        $doctrine->expects($this->any())
            ->method('getManagerForClass')
            ->with('OroSecurityBundle:PermissionEntity')
            ->willReturn($em);

        $this->builder = new PermissionConfigurationBuilder($doctrine);
    }

    protected function tearDown()
    {
        unset($this->builder);
    }

    /**
     * @param array $expected
     * @param Permission $definition
     */
    protected function assertDefinitionConfiguration(array $expected, Permission $definition)
    {
        $this->assertSame($expected['label'], $definition->getLabel());
        $this->assertSame($expected['apply_to_all'], $definition->isApplyToAll());
        $this->assertSame($expected['group_names'], $definition->getGroupNames());
        $this->assertEquals($expected['exclude_entities'], $definition->getExcludeEntities());
        $this->assertEquals($expected['apply_to_entities'], $definition->getApplyToEntities());
        $this->assertSame($expected['description'], $definition->getDescription());
    }

    /**
     * @param string $name
     * @param array $configuration
     * @param array $expected
     * @dataProvider buildPermissionDataProvider
     */
    public function testBuildPermission($name, array $configuration, array $expected)
    {
        $definition = $this->builder->buildPermission($name, $configuration);

        $this->assertInstanceOf('Oro\Bundle\SecurityBundle\Entity\Permission', $definition);
        $this->assertEquals($name, $definition->getName());
        $this->assertDefinitionConfiguration($expected, $definition);
    }

    /**
     * @return array
     */
    public function buildPermissionDataProvider()
    {
        $permissionEntity1 = (new PermissionEntity())->setName('Entity1');
        $permissionEntity2 = (new PermissionEntity())->setName('Entity2');

        return [
            'minimum data' => [
                'name' => 'minimum_name',
                'configuration' => [
                    'label' => 'My Label',
                ],
                'expected' => [
                    'label' => 'My Label',
                    'apply_to_all' => true,
                    'group_names' => [],
                    'exclude_entities' => new ArrayCollection(),
                    'apply_to_entities' => new ArrayCollection(),
                    'description' => '',
                ],
            ],
            'maximum data' => [
                'name' => 'maximum_name',
                'configuration' => [
                    'label' => 'My Label',
                    'apply_to_all' => false,
                    'group_names' => ['frontend'],
                    'exclude_entities' => [$permissionEntity1->getName()],
                    'apply_to_entities' => [$permissionEntity2->getName()],
                    'description' => 'Test description',
                ],
                'expected' => [
                    'label' => 'My Label',
                    'apply_to_all' => false,
                    'group_names' => ['frontend'],
                    'exclude_entities' => new ArrayCollection([$permissionEntity1]),
                    'apply_to_entities' => new ArrayCollection([$permissionEntity2]),
                    'description' => 'Test description',
                ],
            ],
        ];
    }

    /**
     * @param array $configuration
     * @param array $expected
     * @dataProvider buildPermissionsDataProvider
     */
    public function testBuildPermissions(array $configuration, array $expected)
    {
        $permissions = $this->builder->buildPermissions($configuration);

        $this->assertSameSize($expected, $permissions);
        foreach ($permissions as $permission) {
            $this->assertInstanceOf('Oro\Bundle\SecurityBundle\Entity\Permission', $permission);
            $this->assertArrayHasKey($permission->getName(), $expected);
            $this->assertDefinitionConfiguration($expected[$permission->getName()], $permission);
        }
    }

    /**
     * @return array
     */
    public function buildPermissionsDataProvider()
    {
        $basicDataProvider = $this->buildPermissionDataProvider();

        $configuration = [];
        $expected = [];
        foreach ($basicDataProvider as $dataSet) {
            $definitionName = $dataSet['name'];
            $configuration[$definitionName] = $dataSet['configuration'];
            $expected[$definitionName] = $dataSet['expected'];
        }

        return [
            [
                'configuration' => $configuration,
                'expected' => $expected,
            ]
        ];
    }
}
