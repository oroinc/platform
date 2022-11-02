<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\Grid;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\DatagridGuesser;
use Oro\Bundle\DataGridBundle\Datagrid\ParameterBag;
use Oro\Bundle\DataGridBundle\Tests\Unit\Datagrid\ColumnOptionsGuesserMock;
use Oro\Bundle\EntityBundle\EntityConfig\DatagridScope;
use Oro\Bundle\EntityConfigBundle\Config\Config;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\EntityExtendBundle\Grid\AbstractFieldsExtension;
use Oro\Bundle\EntityExtendBundle\Grid\AdditionalFieldsExtension;

class AdditionalFieldsExtensionTest extends AbstractFieldsExtensionTestCase
{
    /**
     * {@inheritDoc}
     */
    protected function getExtension(): AbstractFieldsExtension
    {
        $extension = new AdditionalFieldsExtension(
            $this->configManager,
            $this->entityClassResolver,
            new DatagridGuesser([new ColumnOptionsGuesserMock()]),
            $this->fieldsHelper
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
        $this->assertTrue(
            $this->getExtension()->isApplicable(
                DatagridConfiguration::create(
                    [
                        'options' => [
                            'entity_name' => self::ENTITY_NAME,
                            'additional_fields' => [self::FIELD_NAME],
                        ],
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
                        'options' => [
                            'entity_name' => self::ENTITY_NAME,
                            'additional_fields' => [],
                        ],
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
                        'options' => [
                            'entity_name' => self::ENTITY_NAME,
                        ],
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
                        'options' => [
                            'entity_name' => self::ENTITY_NAME,
                            'additional_fields' => [self::FIELD_NAME],
                        ],
                    ]
                )
            )
        );
    }

    public function testGetPriority()
    {
        $this->assertEquals(
            250,
            $this->getExtension()->getPriority()
        );
    }

    /**
     * {@inheritDoc}
     */
    protected function getDatagridConfiguration(array $options = []): DatagridConfiguration
    {
        return DatagridConfiguration::create(
            array_merge(
                $options,
                [
                    'options' => [
                        'entity_name' => self::ENTITY_NAME,
                        'additional_fields' => [self::FIELD_NAME],
                    ],
                ]
            )
        );
    }

    /**
     * {@inheritDoc}
     */
    protected function setExpectationForGetFields(
        string $className,
        string $fieldName,
        string $fieldType,
        array $extendFieldConfig = []
    ) {
        $extendConfig = new Config(new FieldConfigId('extend', $className, $fieldName, $fieldType));
        $extendConfig->set('state', ExtendScope::STATE_ACTIVE);
        $extendConfig->set('is_deleted', false);
        foreach ($extendFieldConfig as $key => $val) {
            $extendConfig->set($key, $val);
        }

        $entityFieldConfig = new Config(new FieldConfigId('entity', self::ENTITY_CLASS, self::FIELD_NAME, $fieldType));
        $entityFieldConfig->set('label', 'label');

        $datagridFieldConfig = new Config(
            new FieldConfigId('datagrid', self::ENTITY_CLASS, self::FIELD_NAME, $fieldType),
            ['is_visible' => DatagridScope::IS_VISIBLE_TRUE]
        );

        $viewFieldConfig = new Config(
            new FieldConfigId('view', self::ENTITY_CLASS, self::FIELD_NAME, $fieldType)
        );

        $this->configManager->expects($this->once())
            ->method('hasConfig')
            ->with($className)
            ->willReturn(true);

        $this->extendConfigProvider->expects($this->once())
            ->method('hasConfig')
            ->with($className, $fieldName)
            ->willReturn(true);
        $this->extendConfigProvider->expects($this->any())
            ->method('getConfig')
            ->with($className, $fieldName)
            ->willReturn($extendConfig);
        $this->entityConfigProvider->expects($this->any())
            ->method('getConfig')
            ->with(self::ENTITY_CLASS, self::FIELD_NAME)
            ->willReturn($entityFieldConfig);
        $this->datagridConfigProvider->expects($this->any())
            ->method('getConfig')
            ->with(self::ENTITY_CLASS, self::FIELD_NAME)
            ->willReturn($datagridFieldConfig);
        $this->viewConfigProvider->expects($this->any())
            ->method('getConfig')
            ->with(self::ENTITY_CLASS, self::FIELD_NAME)
            ->willReturn($viewFieldConfig);
    }
}
