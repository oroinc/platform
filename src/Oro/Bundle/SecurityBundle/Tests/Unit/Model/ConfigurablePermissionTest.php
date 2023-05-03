<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\Model;

use Oro\Bundle\SecurityBundle\Model\ConfigurablePermission;

class ConfigurablePermissionTest extends \PHPUnit\Framework\TestCase
{
    private const CAPABILITY = 'test_capability';
    private const ENTITY_CLASS = 'test_entity';
    private const WORKFLOW = 'test_workflow';
    private const PERMISSION = 'test_permission';

    public function testGetName()
    {
        $name = 'test_name';
        $model = new ConfigurablePermission('test_name');

        $this->assertEquals($name, $model->getName());
    }

    /**
     * @dataProvider isCapabilityConfigurableDataProvider
     */
    public function testIsCapabilityConfigurable(bool $default, array $capabilities, bool $expected)
    {
        $model = new ConfigurablePermission('test_name', $default, [], $capabilities);

        $this->assertEquals($expected, $model->isCapabilityConfigurable(self::CAPABILITY));
    }

    public function isCapabilityConfigurableDataProvider(): array
    {
        return [
            'capabilities contains true' => [
                'default' => false,
                'capabilities' => [self::CAPABILITY => true],
                'expected' => true,
            ],
            'capabilities contains false' => [
                'default' => true,
                'capabilities' => [self::CAPABILITY => false],
                'expected' => false,
            ],
            'default true, capabilities not contains' => [
                'default' => true,
                'capabilities' => [],
                'expected' => true,
            ],
            'default false, capabilities not contains' => [
                'default' => false,
                'capabilities' => [],
                'expected' => false,
            ],
        ];
    }

    /**
     * @dataProvider isEntityPermissionConfigurableDataProvider
     */
    public function testIsEntityPermissionConfigurable(bool $default, array $entities, bool $expected)
    {
        $model = new ConfigurablePermission('test_name', $default, $entities);

        $this->assertEquals($expected, $model->isEntityPermissionConfigurable(self::ENTITY_CLASS, self::PERMISSION));
    }

    public function isEntityPermissionConfigurableDataProvider(): array
    {
        return [
            'default true, not contains entity' => [
                'default' => true,
                'entities' => [],
                'expected' => true,
            ],
            'default false, not contains entity' => [
                'default' => false,
                'entities' => [],
                'expected' => false,
            ],
            'default true, entity, no permission' => [
                'default' => true,
                'entities' => [self::ENTITY_CLASS => []],
                'expected' => true,
            ],
            'default false, entity, no permission' => [
                'default' => true,
                'entities' => [self::ENTITY_CLASS => []],
                'expected' => true,
            ],
            'contains permission false' => [
                'default' => true,
                'entities' => [self::ENTITY_CLASS => [self::PERMISSION => false]],
                'expected' => false,
            ],
            'contains permission true' => [
                'default' => false,
                'entities' => [self::ENTITY_CLASS => [self::PERMISSION => true]],
                'expected' => true,
            ],
            'boolean' => [
                'default' => false,
                'entities' => [self::ENTITY_CLASS => true],
                'expected' => true,
            ],
        ];
    }

    /**
     * @dataProvider isWorkflowPermissionConfigurableDataProvider
     */
    public function testIsWorkflowPermissionConfigurable(bool $default, array $workflows, bool $expected)
    {
        $model = new ConfigurablePermission('test_name', $default, [], [], $workflows);

        $this->assertEquals($expected, $model->isWorkflowPermissionConfigurable(self::WORKFLOW, self::PERMISSION));
    }

    public function isWorkflowPermissionConfigurableDataProvider(): array
    {
        return [
            'default true, not contains workflow' => [
                'default' => true,
                'workflows' => [],
                'expected' => true,
            ],
            'default false, not contains workflow' => [
                'default' => false,
                'workflows' => [],
                'expected' => false,
            ],
            'default true, workflow, no permission' => [
                'default' => true,
                'workflows' => [self::WORKFLOW => []],
                'expected' => true,
            ],
            'default false, workflow, no permission' => [
                'default' => true,
                'workflows' => [self::WORKFLOW => []],
                'expected' => true,
            ],
            'contains permission false' => [
                'default' => true,
                'workflows' => [self::WORKFLOW => [self::PERMISSION => false]],
                'expected' => false,
            ],
            'contains permission true' => [
                'default' => false,
                'workflows' => [self::WORKFLOW => [self::PERMISSION => true]],
                'expected' => true,
            ],
            'boolean' => [
                'default' => true,
                'workflows' => [self::WORKFLOW => false],
                'expected' => false,
            ],
        ];
    }
}
