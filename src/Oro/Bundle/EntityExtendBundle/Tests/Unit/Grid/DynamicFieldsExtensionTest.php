<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\Grid;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Tests\Unit\Datagrid\DatagridGuesserMock;
use Oro\Bundle\EntityBundle\EntityConfig\DatagridScope;
use Oro\Bundle\EntityConfigBundle\Config\Config;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\EntityExtendBundle\Grid\DynamicFieldsExtension;

class DynamicFieldsExtensionTest extends AbstractFieldsExtensionTestCase
{
    /** {@inheritdoc} */
    protected function getExtension()
    {
        return new DynamicFieldsExtension(
            $this->configManager,
            $this->entityClassResolver,
            new DatagridGuesserMock()
        );
    }

    public function testIsApplicable()
    {
        $this->assertFalse(
            $this->getExtension()->isApplicable(
                DatagridConfiguration::create(
                    [
                        'source' => [
                            'type' => 'orm',
                        ],
                    ]
                )
            )
        );
        $this->assertTrue(
            $this->getExtension()->isApplicable(
                DatagridConfiguration::create(
                    [
                        'extended_entity_name' => 'entity',
                        'source' => [
                            'type' => 'orm',
                        ],
                    ]
                )
            )
        );
        $this->assertFalse(
            $this->getExtension()->isApplicable(
                DatagridConfiguration::create(
                    [
                        'extended_entity_name' => 'entity',
                    ]
                )
            )
        );
    }

    public function testGetPriority()
    {
        $this->assertEquals(
            300,
            $this->getExtension()->getPriority()
        );
    }

    public function testProcessConfigsWithVisibleFilter()
    {
        $fieldType = 'string';

        $this->entityClassResolver->expects($this->atLeastOnce())
            ->method('getEntityClass')
            ->with(self::ENTITY_NAME)
            ->will($this->returnValue(self::ENTITY_CLASS));

        $this->setExpectationForGetFields(self::ENTITY_CLASS, self::FIELD_NAME, $fieldType);

        $config = $this->getDatagridConfiguration();
        $initialConfig = $config->toArray();
        $this->getExtension()->processConfigs($config);
        $this->assertEquals(
            array_merge(
                $initialConfig,
                [
                    'columns' => [
                        self::FIELD_NAME => [
                            'label' => 'label',
                            'frontend_type' => 'string',
                            'renderable' => true,
                            'required' => false,
                            'data_name' => 'testField',
                        ],
                    ],
                    'sorters' => [
                        'columns' => [
                            self::FIELD_NAME => [
                                'data_name' => 'o.'.self::FIELD_NAME,
                            ],
                        ],
                    ],
                    'filters' => [
                        'columns' => [
                            self::FIELD_NAME => [
                                'type' => 'string',
                                'data_name' => 'o.'.self::FIELD_NAME,
                                'enabled' => true,
                            ],
                        ],
                    ],
                    'source' => [
                        'query' => [
                            'from' => [['table' => 'Test\Entity', 'alias' => 'o']],
                            'select' => ['o.testField'],
                        ],
                    ],
                    'fields_acl' => ['columns' => ['testField' => ['data_name' => 'o.testField']]],
                ]
            ),
            $config->toArray()
        );
    }


    /** {@inheritdoc} */
    protected function getDatagridConfiguration(array $options = [])
    {
        return DatagridConfiguration::create(array_merge($options, ['extended_entity_name' => self::ENTITY_NAME]));
    }

    /** {@inheritdoc} */
    protected function setExpectationForGetFields($className, $fieldName, $fieldType, array $extendFieldConfig = [])
    {
        $fieldId = new FieldConfigId('entity', $className, $fieldName, $fieldType);

        $extendConfig = new Config(new FieldConfigId('extend', $className, $fieldName, $fieldType));
        $extendConfig->set('owner', ExtendScope::OWNER_CUSTOM);
        $extendConfig->set('state', ExtendScope::STATE_ACTIVE);
        foreach ($extendFieldConfig as $key => $value) {
            $extendConfig->set($key, $value);
        }
        $extendConfig->set('is_deleted', false);

        $entityFieldConfig = new Config(new FieldConfigId('entity', self::ENTITY_CLASS, self::FIELD_NAME, $fieldType));
        $entityFieldConfig->set('label', 'label');

        $datagridFieldConfig = new Config(
            new FieldConfigId('datagrid', self::ENTITY_CLASS, self::FIELD_NAME, $fieldType)
        );
        $datagridFieldConfig->set('show_filter', true);
        $datagridFieldConfig->set('is_visible', DatagridScope::IS_VISIBLE_TRUE);

        $viewFieldConfig = new Config(
            new FieldConfigId('view', self::ENTITY_CLASS, self::FIELD_NAME, $fieldType)
        );

        $this->configManager->expects($this->once())
            ->method('hasConfig')
            ->with($className)
            ->will($this->returnValue(true));

        $this->entityConfigProvider->expects($this->once())
            ->method('getConfig')
            ->with(self::ENTITY_CLASS, self::FIELD_NAME)
            ->will($this->returnValue($entityFieldConfig));
        $this->entityConfigProvider->expects($this->once())
            ->method('getIds')
            ->with($className)
            ->will($this->returnValue([$fieldId]));
        $this->extendConfigProvider->expects($this->once())
            ->method('getConfigById')
            ->with($this->identicalTo($fieldId))
            ->will($this->returnValue($extendConfig));
        $this->extendConfigProvider->expects($this->any())
            ->method('getConfig')
            ->with(self::ENTITY_CLASS, self::FIELD_NAME)
            ->will($this->returnValue($extendConfig));
        $this->datagridConfigProvider->expects($this->once())
            ->method('getConfigById')
            ->with($this->identicalTo($fieldId))
            ->will($this->returnValue($datagridFieldConfig));
        $this->datagridConfigProvider->expects($this->any())
            ->method('getConfig')
            ->with(self::ENTITY_CLASS, self::FIELD_NAME)
            ->will($this->returnValue($datagridFieldConfig));
        $this->viewConfigProvider->expects($this->any())
            ->method('getConfig')
            ->with(self::ENTITY_CLASS, self::FIELD_NAME)
            ->will($this->returnValue($viewFieldConfig));
    }
}
