<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\Model;

use Oro\Bundle\SecurityBundle\Model\ConfigurablePermission;

class ConfigurablePermissionTest extends \PHPUnit_Framework_TestCase
{
    const CAPABILITY = 'test_capability';
    const ENTITY_CLASS = 'test_entity';
    const PERMISSION = 'test_permission';

    public function testGetName()
    {
        $name = 'test_name';
        $model = new ConfigurablePermission('test_name');

        $this->assertEquals($name, $model->getName());
    }

    /**
     * @dataProvider isCapabilityConfigurableDataProvider
     *
     * @param bool $default
     * @param array $capabilities
     * @param bool $expected
     */
    public function testIsCapabilityConfigurable($default, array $capabilities, $expected)
    {
        $model = new ConfigurablePermission('test_name', $default, [], $capabilities);

        $this->assertEquals($expected, $model->isCapabilityConfigurable(self::CAPABILITY));
    }

    /**
     * @return array
     */
    public function isCapabilityConfigurableDataProvider()
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
     *
     * @param bool $default
     * @param array $entities
     * @param bool $expected
     */
    public function testIsEntityPermissionConfigurable($default, array $entities, $expected)
    {
        $model = new ConfigurablePermission('test_name', $default, $entities);

        $this->assertEquals($expected, $model->isEntityPermissionConfigurable(self::ENTITY_CLASS, self::PERMISSION));
    }

    /**
     * @return array
     */
    public function isEntityPermissionConfigurableDataProvider()
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
        ];
    }
}
