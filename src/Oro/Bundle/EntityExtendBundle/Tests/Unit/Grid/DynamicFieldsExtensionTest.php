<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\Grid;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\ParameterBag;
use Oro\Bundle\DataGridBundle\Tests\Unit\Datagrid\DatagridGuesserMock;
use Oro\Bundle\EntityBundle\EntityConfig\DatagridScope;
use Oro\Bundle\EntityConfigBundle\Config\Config;
use Oro\Bundle\EntityConfigBundle\Config\ConfigInterface;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\EntityExtendBundle\Grid\DynamicFieldsExtension;

class DynamicFieldsExtensionTest extends AbstractFieldsExtensionTestCase
{
    /** {@inheritdoc} */
    protected function getExtension()
    {
        $extension = new DynamicFieldsExtension(
            $this->configManager,
            $this->entityClassResolver,
            new DatagridGuesserMock(),
            $this->fieldsHelper,
            $this->getFeatureCheckerMock()
        );

        $extension->setParameters(new ParameterBag());

        return $extension;
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

    public function testIsApplicableWhenHasNoConfig()
    {
        $datagridConfig = DatagridConfiguration::create([
            'extended_entity_name' => self::ENTITY_NAME,
            'source' => [
                'type' => 'orm',
            ],
        ]);

        $this->extendConfigProvider
            ->expects($this->once())
            ->method('hasConfig')
            ->with(self::ENTITY_CLASS)
            ->willReturn(false);

        $this->assertFalse($this->getExtension()->isApplicable($datagridConfig));
    }

    /**
     * @return array
     */
    public function isExtendDataProvider()
    {
        return [
            'is applicable' => [
                'isExtend' => true
            ],
            'not applicable' => [
                'isExtend' => false
            ],
        ];
    }

    /**
     * @dataProvider isExtendDataProvider
     * @param bool $isExtend
     */
    public function testIsApplicableIfEntityIsExtendable($isExtend)
    {
        $datagridConfig = DatagridConfiguration::create([
            'extended_entity_name' => self::ENTITY_NAME,
            'source' => [
                'type' => 'orm',
            ],
        ]);

        $config = $this->createMock(ConfigInterface::class);
        $config
            ->expects($this->once())
            ->method('is')
            ->with('is_extend')
            ->willReturn($isExtend);

        $this->extendConfigProvider
            ->expects($this->once())
            ->method('getConfig')
            ->with(self::ENTITY_CLASS)
            ->willReturn($config);

        $this->extendConfigProvider
            ->expects($this->once())
            ->method('hasConfig')
            ->with(self::ENTITY_CLASS)
            ->willReturn(true);

        $this->assertEquals($isExtend, $this->getExtension()->isApplicable($datagridConfig));
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
                            'order' => 0
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
                            'from' => [['table' => self::ENTITY_CLASS, 'alias' => 'o']],
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

        $this->fieldsHelper->expects($this->once())
            ->method('getFields')
            ->willReturn([$fieldId]);

        $this->entityConfigProvider->expects($this->once())
            ->method('getConfig')
            ->with(self::ENTITY_CLASS, self::FIELD_NAME)
            ->will($this->returnValue($entityFieldConfig));

        $this->extendConfigProvider->expects($this->any())
            ->method('getConfig')
            ->with(self::ENTITY_CLASS, self::FIELD_NAME)
            ->will($this->returnValue($extendConfig));

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
