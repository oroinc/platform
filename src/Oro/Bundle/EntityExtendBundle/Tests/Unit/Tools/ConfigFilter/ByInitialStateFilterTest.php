<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\Tools\ConfigFilter;

use Oro\Bundle\EntityConfigBundle\Config\Config;
use Oro\Bundle\EntityConfigBundle\Config\ConfigInterface;
use Oro\Bundle\EntityConfigBundle\Config\Id\EntityConfigId;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\EntityExtendBundle\Tools\ConfigFilter\ByInitialStateFilter;

class ByInitialStateFilterTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider applyDataProvider
     */
    public function testApply(ConfigInterface $config, $expectedResult)
    {
        $filter = new ByInitialStateFilter(
            [
                'entities' => [
                    'Test\ActiveEntity'        => ExtendScope::STATE_ACTIVE,
                    'Test\NewEntity'           => ExtendScope::STATE_NEW,
                    'Test\RequireUpdateEntity' => ExtendScope::STATE_UPDATE,
                    'Test\ToDeleteEntity'      => ExtendScope::STATE_DELETE,
                ],
                'fields'   => [
                    'Test\Entity' => [
                        'active_field'         => ExtendScope::STATE_ACTIVE,
                        'new_field'            => ExtendScope::STATE_NEW,
                        'require_update_field' => ExtendScope::STATE_UPDATE,
                        'to_delete_field'      => ExtendScope::STATE_DELETE,
                    ]
                ],
            ]
        );
        $this->assertEquals($expectedResult, call_user_func($filter, $config));
    }

    public function applyDataProvider()
    {
        return [
            'active entity'                                              => [
                $this->getEntityConfig(
                    'Test\ActiveEntity',
                    ['state' => ExtendScope::STATE_ACTIVE]
                ),
                true
            ],
            'active field'                                               => [
                $this->getFieldConfig(
                    'Test\ActiveEntity',
                    'active_field',
                    ['state' => ExtendScope::STATE_ACTIVE]
                ),
                true
            ],
            'created, but not committed entity'                          => [
                $this->getEntityConfig(
                    'Test\NewEntity',
                    ['state' => ExtendScope::STATE_NEW]
                ),
                false
            ],
            'created, but not committed field'                           => [
                $this->getFieldConfig(
                    'Test\Entity',
                    'new_field',
                    ['state' => ExtendScope::STATE_NEW]
                ),
                false
            ],
            'created in a migration entity'                              => [
                $this->getEntityConfig(
                    'Test\CreatedEntity',
                    ['state' => ExtendScope::STATE_NEW]
                ),
                true
            ],
            'created in a migration field'                               => [
                $this->getFieldConfig(
                    'Test\Entity',
                    'created_field',
                    ['state' => ExtendScope::STATE_NEW]
                ),
                true
            ],
            'updated, but not committed entity'                          => [
                $this->getEntityConfig(
                    'Test\RequireUpdateEntity',
                    ['state' => ExtendScope::STATE_ACTIVE]
                ),
                false
            ],
            'updated, but not committed field'                           => [
                $this->getFieldConfig(
                    'Test\Entity',
                    'require_update_field',
                    ['state' => ExtendScope::STATE_ACTIVE]
                ),
                false
            ],
            'marked as to be deleted entity'                             => [
                $this->getEntityConfig(
                    'Test\ToDeleteEntity',
                    ['state' => ExtendScope::STATE_DELETE]
                ),
                false
            ],
            'marked as to be deleted field'                              => [
                $this->getFieldConfig(
                    'Test\Entity',
                    'to_delete_field',
                    ['state' => ExtendScope::STATE_DELETE]
                ),
                false
            ],
            'marked as to be deleted, but changed in a migration entity' => [
                $this->getEntityConfig(
                    'Test\ToDeleteEntity',
                    ['state' => ExtendScope::STATE_UPDATE]
                ),
                true
            ],
            'marked as to be deleted, but changed in a migration field'  => [
                $this->getFieldConfig(
                    'Test\Entity',
                    'to_delete_field',
                    ['state' => ExtendScope::STATE_UPDATE]
                ),
                true
            ],
        ];
    }

    /**
     * @param string $className
     * @param mixed  $values
     *
     * @return ConfigInterface
     */
    protected function getEntityConfig($className, $values)
    {
        $configId = new EntityConfigId('extend', $className);
        $config   = new Config($configId);
        $config->setValues($values);

        return $config;
    }

    /**
     * @param string $className
     * @param string $fieldName
     * @param mixed  $values
     *
     * @return ConfigInterface
     */
    protected function getFieldConfig($className, $fieldName, $values)
    {
        $configId = new FieldConfigId('extend', $className, $fieldName);
        $config   = new Config($configId);
        $config->setValues($values);

        return $config;
    }
}
