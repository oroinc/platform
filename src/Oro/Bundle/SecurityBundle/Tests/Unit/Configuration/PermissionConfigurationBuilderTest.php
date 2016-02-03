<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\Configuration;

use Oro\Bundle\SecurityBundle\Configuration\PermissionConfigurationBuilder;
use Oro\Bundle\SecurityBundle\Entity\Permission;

class PermissionConfigurationBuilderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var PermissionConfigurationBuilder
     */
    protected $builder;

    protected function setUp()
    {
        $this->builder = new PermissionConfigurationBuilder();
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
        $this->assertSame($expected['excluded_entities'], $definition->getExcludeEntities());
        $this->assertSame($expected['apply_to_entities'], $definition->getApplyToEntities());
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
                    'excluded_entities' => [],
                    'apply_to_entities' => [],
                    'description' => '',
                ],
            ],
            'maximum data' => [
                'name' => 'maximum_name',
                'configuration' => [
                    'label' => 'My Label',
                    'apply_to_all' => false,
                    'group_names' => ['frontend'],
                    'excluded_entities' => ['Entity1'],
                    'apply_to_entities' => ['Entity2'],
                    'description' => 'Test description',
                ],
                'expected' => [
                    'label' => 'My Label',
                    'apply_to_all' => false,
                    'group_names' => ['frontend'],
                    'excluded_entities' => ['Entity1'],
                    'apply_to_entities' => ['Entity2'],
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
