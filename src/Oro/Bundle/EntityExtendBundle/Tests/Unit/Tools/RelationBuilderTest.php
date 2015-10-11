<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\Tools;

use Oro\Bundle\EntityConfigBundle\Config\Config;
use Oro\Bundle\EntityConfigBundle\Config\Id\EntityConfigId;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\EntityExtendBundle\Tools\RelationBuilder;

class RelationBuilderTest extends \PHPUnit_Framework_TestCase
{
    const SOURCE_CLASS = 'Test\SourceEntity';
    const TARGET_CLASS = 'Test\TargetEntity';

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $configManager;

    /** @var RelationBuilder */
    protected $builder;

    public function setUp()
    {
        $this->configManager = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Config\ConfigManager')
            ->disableOriginalConstructor()
            ->getMock();
        $this->builder       = new RelationBuilder($this->configManager);
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testAddManyToOneRelationForNewRelation()
    {
        $relationName    = 'testRelation';
        $relationKey     = 'manyToOne|Test\SourceEntity|Test\TargetEntity|testRelation';
        $targetFieldName = 'field1';

        $extendConfig      = new Config(new EntityConfigId('extend', self::SOURCE_CLASS));
        $extendFieldConfig = new Config(new FieldConfigId('extend', self::SOURCE_CLASS, $relationName, 'manyToOne'));
        $testFieldConfig   = new Config(new FieldConfigId('test', self::SOURCE_CLASS, $relationName, 'manyToOne'));

        $expectedExtendConfig = new Config($extendConfig->getId());
        $expectedExtendConfig->set(
            'relation',
            [
                $relationKey => [
                    'field_id'        => new FieldConfigId('extend', self::SOURCE_CLASS, $relationName, 'manyToOne'),
                    'owner'           => true,
                    'target_entity'   => self::TARGET_CLASS,
                    'target_field_id' => false
                ]
            ]
        );

        $expectedExtendFieldConfig = new Config($extendFieldConfig->getId());
        $expectedExtendFieldConfig->setValues(
            [
                'owner'         => ExtendScope::OWNER_CUSTOM,
                'is_extend'     => true,
                'state'         => ExtendScope::STATE_NEW,
                'relation_key'  => $relationKey,
                'target_entity' => self::TARGET_CLASS,
                'target_field'  => $targetFieldName
            ]
        );

        $expectedTestFieldConfig = new Config($testFieldConfig->getId());
        $expectedTestFieldConfig->setValues(
            [
                'test_attr' => 123
            ]
        );

        $extendConfigProvider = $this->getConfigProviderMock();
        $extendConfigProvider->expects($this->once())
            ->method('getConfig')
            ->with(self::SOURCE_CLASS, $relationName)
            ->will($this->returnValue($extendFieldConfig));
        $this->configManager->expects($this->at(3))
            ->method('persist')
            ->with($this->identicalTo($extendFieldConfig));
        $this->configManager->expects($this->at(6))
            ->method('persist')
            ->with($this->identicalTo($extendConfig));

        $testConfigProvider = $this->getConfigProviderMock();
        $testConfigProvider->expects($this->once())
            ->method('getConfig')
            ->with(self::SOURCE_CLASS, $relationName)
            ->will($this->returnValue($testFieldConfig));
        $this->configManager->expects($this->at(5))
            ->method('persist')
            ->with($this->identicalTo($testFieldConfig));

        $this->configManager->expects($this->once())
            ->method('hasConfigFieldModel')
            ->with(self::SOURCE_CLASS, $relationName)
            ->will($this->returnValue(false));
        $this->configManager->expects($this->once())
            ->method('createConfigFieldModel')
            ->with(self::SOURCE_CLASS, $relationName, 'manyToOne');
        $this->configManager->expects($this->any())
            ->method('getProvider')
            ->will(
                $this->returnValueMap(
                    [
                        ['extend', $extendConfigProvider],
                        ['test', $testConfigProvider],
                    ]
                )
            );

        $this->builder->addManyToOneRelation(
            $extendConfig,
            self::TARGET_CLASS,
            $relationName,
            $targetFieldName,
            [
                'extend' => [
                    'owner' => ExtendScope::OWNER_CUSTOM
                ],
                'test'   => [
                    'test_attr' => 123
                ]
            ]
        );

        $this->assertEquals($expectedExtendConfig, $extendConfig);
        $this->assertEquals($expectedExtendFieldConfig, $extendFieldConfig);
        $this->assertEquals($expectedTestFieldConfig, $testFieldConfig);
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testAddManyToManyRelationForNewRelation()
    {
        $relationName            = 'testRelation';
        $relationKey             = 'manyToMany|Test\SourceEntity|Test\TargetEntity|testRelation';
        $targetTitleFieldName    = 'field1';
        $targetDetailedFieldName = 'field2';
        $targetGridFieldName     = 'field3';

        $extendConfig      = new Config(new EntityConfigId('extend', self::SOURCE_CLASS));
        $extendFieldConfig = new Config(new FieldConfigId('extend', self::SOURCE_CLASS, $relationName, 'manyToMany'));
        $testFieldConfig   = new Config(new FieldConfigId('test', self::SOURCE_CLASS, $relationName, 'manyToOne'));

        $expectedExtendConfig = new Config(new EntityConfigId('extend', self::SOURCE_CLASS));
        $expectedExtendConfig->set(
            'relation',
            [
                $relationKey => [
                    'field_id'        => new FieldConfigId('extend', self::SOURCE_CLASS, $relationName, 'manyToMany'),
                    'owner'           => true,
                    'target_entity'   => self::TARGET_CLASS,
                    'target_field_id' => false,
                ]
            ]
        );

        $expectedExtendFieldConfig = new Config($extendFieldConfig->getId());
        $expectedExtendFieldConfig->setValues(
            [
                'owner'           => ExtendScope::OWNER_CUSTOM,
                'is_extend'       => true,
                'state'           => ExtendScope::STATE_NEW,
                'relation_key'    => $relationKey,
                'target_entity'   => self::TARGET_CLASS,
                'target_title'    => [$targetTitleFieldName],
                'target_detailed' => [$targetDetailedFieldName],
                'target_grid'     => [$targetGridFieldName],
            ]
        );

        $expectedTestFieldConfig = new Config($testFieldConfig->getId());
        $expectedTestFieldConfig->setValues(
            [
                'test_attr' => 123
            ]
        );

        $extendConfigProvider = $this->getConfigProviderMock();
        $extendConfigProvider->expects($this->once())
            ->method('getConfig')
            ->with(self::SOURCE_CLASS, $relationName)
            ->will($this->returnValue($extendFieldConfig));
        $this->configManager->expects($this->at(3))
            ->method('persist')
            ->with($this->identicalTo($extendFieldConfig));
        $this->configManager->expects($this->at(6))
            ->method('persist')
            ->with($this->identicalTo($extendConfig));

        $testConfigProvider = $this->getConfigProviderMock();
        $testConfigProvider->expects($this->once())
            ->method('getConfig')
            ->with(self::SOURCE_CLASS, $relationName)
            ->will($this->returnValue($testFieldConfig));
        $this->configManager->expects($this->at(5))
            ->method('persist')
            ->with($this->identicalTo($testFieldConfig));

        $this->configManager->expects($this->once())
            ->method('hasConfigFieldModel')
            ->with(self::SOURCE_CLASS, $relationName)
            ->will($this->returnValue(false));
        $this->configManager->expects($this->once())
            ->method('createConfigFieldModel')
            ->with(self::SOURCE_CLASS, $relationName, 'manyToMany');
        $this->configManager->expects($this->any())
            ->method('getProvider')
            ->will(
                $this->returnValueMap(
                    [
                        ['extend', $extendConfigProvider],
                        ['test', $testConfigProvider],
                    ]
                )
            );

        $this->builder->addManyToManyRelation(
            $extendConfig,
            self::TARGET_CLASS,
            $relationName,
            [$targetTitleFieldName],
            [$targetDetailedFieldName],
            [$targetGridFieldName],
            [
                'extend' => [
                    'owner' => ExtendScope::OWNER_CUSTOM
                ],
                'test'   => [
                    'test_attr' => 123
                ]
            ]
        );

        $this->assertEquals($expectedExtendConfig, $extendConfig);
        $this->assertEquals($expectedExtendFieldConfig, $extendFieldConfig);
        $this->assertEquals($expectedTestFieldConfig, $testFieldConfig);
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testAddManyToOneRelationWithCascade()
    {
        $relationName    = 'testRelation';
        $relationKey     = 'manyToOne|Test\SourceEntity|Test\TargetEntity|testRelation';
        $targetFieldName = 'field1';

        $extendConfig      = new Config(new EntityConfigId('extend', self::SOURCE_CLASS));
        $extendFieldConfig = new Config(new FieldConfigId('extend', self::SOURCE_CLASS, $relationName, 'manyToOne'));
        $testFieldConfig   = new Config(new FieldConfigId('test', self::SOURCE_CLASS, $relationName, 'manyToOne'));

        $expectedExtendConfig = new Config($extendConfig->getId());
        $expectedExtendConfig->set(
            'relation',
            [
                $relationKey => [
                    'field_id'        => new FieldConfigId('extend', self::SOURCE_CLASS, $relationName, 'manyToOne'),
                    'owner'           => true,
                    'target_entity'   => self::TARGET_CLASS,
                    'target_field_id' => false,
                    'cascade'         => ['persist', 'remove']
                ]
            ]
        );

        $expectedExtendFieldConfig = new Config($extendFieldConfig->getId());
        $expectedExtendFieldConfig->setValues(
            [
                'owner'         => ExtendScope::OWNER_CUSTOM,
                'is_extend'     => true,
                'state'         => ExtendScope::STATE_NEW,
                'relation_key'  => $relationKey,
                'target_entity' => self::TARGET_CLASS,
                'target_field'  => $targetFieldName,
                'cascade'       => ['persist', 'remove']
            ]
        );

        $expectedTestFieldConfig = new Config($testFieldConfig->getId());
        $expectedTestFieldConfig->setValues(
            [
                'test_attr' => 123
            ]
        );

        $extendConfigProvider = $this->getConfigProviderMock();
        $extendConfigProvider->expects($this->once())
            ->method('getConfig')
            ->with(self::SOURCE_CLASS, $relationName)
            ->will($this->returnValue($extendFieldConfig));
        $this->configManager->expects($this->at(3))
            ->method('persist')
            ->with($this->identicalTo($extendFieldConfig));
        $this->configManager->expects($this->at(6))
            ->method('persist')
            ->with($this->identicalTo($extendConfig));

        $testConfigProvider = $this->getConfigProviderMock();
        $testConfigProvider->expects($this->once())
            ->method('getConfig')
            ->with(self::SOURCE_CLASS, $relationName)
            ->will($this->returnValue($testFieldConfig));
        $this->configManager->expects($this->at(5))
            ->method('persist')
            ->with($this->identicalTo($testFieldConfig));

        $this->configManager->expects($this->once())
            ->method('hasConfigFieldModel')
            ->with(self::SOURCE_CLASS, $relationName)
            ->will($this->returnValue(false));
        $this->configManager->expects($this->once())
            ->method('createConfigFieldModel')
            ->with(self::SOURCE_CLASS, $relationName, 'manyToOne');
        $this->configManager->expects($this->any())
            ->method('getProvider')
            ->will(
                $this->returnValueMap(
                    [
                        ['extend', $extendConfigProvider],
                        ['test', $testConfigProvider],
                    ]
                )
            );

        $this->builder->addManyToOneRelation(
            $extendConfig,
            self::TARGET_CLASS,
            $relationName,
            $targetFieldName,
            [
                'extend' => [
                    'owner'   => ExtendScope::OWNER_CUSTOM,
                    'cascade' => ['persist', 'remove']
                ],
                'test'   => [
                    'test_attr' => 123
                ]
            ]
        );

        $this->assertEquals($expectedExtendConfig, $extendConfig);
        $this->assertEquals($expectedExtendFieldConfig, $extendFieldConfig);
        $this->assertEquals($expectedTestFieldConfig, $testFieldConfig);
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testAddManyToManyRelationWithCascade()
    {
        $relationName            = 'testRelation';
        $relationKey             = 'manyToMany|Test\SourceEntity|Test\TargetEntity|testRelation';
        $targetTitleFieldName    = 'field1';
        $targetDetailedFieldName = 'field2';
        $targetGridFieldName     = 'field3';

        $extendConfig      = new Config(new EntityConfigId('extend', self::SOURCE_CLASS));
        $extendFieldConfig = new Config(new FieldConfigId('extend', self::SOURCE_CLASS, $relationName, 'manyToMany'));
        $testFieldConfig   = new Config(new FieldConfigId('test', self::SOURCE_CLASS, $relationName, 'manyToOne'));

        $expectedExtendConfig = new Config(new EntityConfigId('extend', self::SOURCE_CLASS));
        $expectedExtendConfig->set(
            'relation',
            [
                $relationKey => [
                    'field_id'        => new FieldConfigId('extend', self::SOURCE_CLASS, $relationName, 'manyToMany'),
                    'owner'           => true,
                    'target_entity'   => self::TARGET_CLASS,
                    'target_field_id' => false,
                    'cascade'         => ['persist', 'remove']
                ]
            ]
        );

        $expectedExtendFieldConfig = new Config($extendFieldConfig->getId());
        $expectedExtendFieldConfig->setValues(
            [
                'owner'           => ExtendScope::OWNER_CUSTOM,
                'is_extend'       => true,
                'state'           => ExtendScope::STATE_NEW,
                'relation_key'    => $relationKey,
                'target_entity'   => self::TARGET_CLASS,
                'target_title'    => [$targetTitleFieldName],
                'target_detailed' => [$targetDetailedFieldName],
                'target_grid'     => [$targetGridFieldName],
                'cascade'         => ['persist', 'remove']
            ]
        );

        $expectedTestFieldConfig = new Config($testFieldConfig->getId());
        $expectedTestFieldConfig->setValues(
            [
                'test_attr' => 123
            ]
        );

        $extendConfigProvider = $this->getConfigProviderMock();
        $extendConfigProvider->expects($this->once())
            ->method('getConfig')
            ->with(self::SOURCE_CLASS, $relationName)
            ->will($this->returnValue($extendFieldConfig));
        $this->configManager->expects($this->at(3))
            ->method('persist')
            ->with($this->identicalTo($extendFieldConfig));
        $this->configManager->expects($this->at(6))
            ->method('persist')
            ->with($this->identicalTo($extendConfig));

        $testConfigProvider = $this->getConfigProviderMock();
        $testConfigProvider->expects($this->once())
            ->method('getConfig')
            ->with(self::SOURCE_CLASS, $relationName)
            ->will($this->returnValue($testFieldConfig));
        $this->configManager->expects($this->at(5))
            ->method('persist')
            ->with($this->identicalTo($testFieldConfig));

        $this->configManager->expects($this->once())
            ->method('hasConfigFieldModel')
            ->with(self::SOURCE_CLASS, $relationName)
            ->will($this->returnValue(false));
        $this->configManager->expects($this->once())
            ->method('createConfigFieldModel')
            ->with(self::SOURCE_CLASS, $relationName, 'manyToMany');
        $this->configManager->expects($this->any())
            ->method('getProvider')
            ->will(
                $this->returnValueMap(
                    [
                        ['extend', $extendConfigProvider],
                        ['test', $testConfigProvider],
                    ]
                )
            );

        $this->builder->addManyToManyRelation(
            $extendConfig,
            self::TARGET_CLASS,
            $relationName,
            [$targetTitleFieldName],
            [$targetDetailedFieldName],
            [$targetGridFieldName],
            [
                'extend' => [
                    'owner'   => ExtendScope::OWNER_CUSTOM,
                    'cascade' => ['persist', 'remove']
                ],
                'test'   => [
                    'test_attr' => 123
                ]
            ]
        );

        $this->assertEquals($expectedExtendConfig, $extendConfig);
        $this->assertEquals($expectedExtendFieldConfig, $extendFieldConfig);
        $this->assertEquals($expectedTestFieldConfig, $testFieldConfig);
    }

    public function testAddManyToOneRelationForAlreadyExistRelationWithDifferentFieldType()
    {
        $relationName    = 'testRelation';
        $relationKey     = 'manyToOne|Test\SourceEntity|Test\TargetEntity|testRelation';
        $targetFieldName = 'field1';
        $newFieldType    = 'test_type';

        $extendConfig = new Config(new EntityConfigId('extend', self::SOURCE_CLASS));
        $extendConfig->set(
            'relation',
            [
                $relationKey => []
            ]
        );
        $extendFieldConfig = new Config(new FieldConfigId('extend', self::SOURCE_CLASS, $relationName, 'manyToOne'));

        $expectedExtendFieldConfig = new Config($extendFieldConfig->getId());
        $expectedExtendFieldConfig->setValues(
            [
                'is_extend'     => true,
                'relation_key'  => $relationKey,
                'target_entity' => self::TARGET_CLASS,
                'target_field'  => $targetFieldName
            ]
        );

        $fieldConfigModel = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel')
            ->disableOriginalConstructor()
            ->getMock();

        $this->configManager->expects($this->once())
            ->method('hasConfigFieldModel')
            ->with(self::SOURCE_CLASS, $relationName)
            ->will($this->returnValue(true));
        $this->configManager->expects($this->never())
            ->method('createConfigFieldModel');

        $this->configManager->expects($this->once())
            ->method('getConfigFieldModel')
            ->with(self::SOURCE_CLASS, $relationName)
            ->will($this->returnValue($fieldConfigModel));
        $fieldConfigModel->expects($this->once())
            ->method('getType')
            ->will($this->returnValue('manyToOne'));
        $this->configManager->expects($this->once())
            ->method('changeFieldType')
            ->with(self::SOURCE_CLASS, $relationName, $newFieldType);

        $extendConfigProvider = $this->getConfigProviderMock();
        $extendConfigProvider->expects($this->once())
            ->method('getConfig')
            ->with(self::SOURCE_CLASS, $relationName)
            ->will($this->returnValue($extendFieldConfig));
        $this->configManager->expects($this->once())
            ->method('persist')
            ->with($this->identicalTo($extendFieldConfig));

        $this->configManager->expects($this->any())
            ->method('getProvider')
            ->will(
                $this->returnValueMap(
                    [
                        ['extend', $extendConfigProvider],
                    ]
                )
            );

        $this->builder->addManyToOneRelation(
            $extendConfig,
            self::TARGET_CLASS,
            $relationName,
            $targetFieldName,
            [],
            $newFieldType
        );

        $this->assertEquals($expectedExtendFieldConfig, $extendFieldConfig);
    }

    public function testAddManyToManyRelationForAlreadyExistRelationWithDifferentFieldType()
    {
        $relationName            = 'testRelation';
        $relationKey             = 'manyToMany|Test\SourceEntity|Test\TargetEntity|testRelation';
        $targetTitleFieldName    = 'field1';
        $targetDetailedFieldName = 'field2';
        $targetGridFieldName     = 'field3';
        $newFieldType            = 'test_type';

        $extendConfig = new Config(new EntityConfigId('extend', self::SOURCE_CLASS));
        $extendConfig->set(
            'relation',
            [
                $relationKey => []
            ]
        );
        $extendFieldConfig = new Config(new FieldConfigId('extend', self::SOURCE_CLASS, $relationName, 'manyToMany'));

        $expectedExtendFieldConfig = new Config($extendFieldConfig->getId());
        $expectedExtendFieldConfig->setValues(
            [
                'is_extend'       => true,
                'relation_key'    => $relationKey,
                'target_entity'   => self::TARGET_CLASS,
                'target_title'    => [$targetTitleFieldName],
                'target_detailed' => [$targetDetailedFieldName],
                'target_grid'     => [$targetGridFieldName],
            ]
        );

        $fieldConfigModel = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel')
            ->disableOriginalConstructor()
            ->getMock();

        $this->configManager->expects($this->once())
            ->method('hasConfigFieldModel')
            ->with(self::SOURCE_CLASS, $relationName)
            ->will($this->returnValue(true));
        $this->configManager->expects($this->never())
            ->method('createConfigFieldModel');

        $this->configManager->expects($this->once())
            ->method('getConfigFieldModel')
            ->with(self::SOURCE_CLASS, $relationName)
            ->will($this->returnValue($fieldConfigModel));
        $fieldConfigModel->expects($this->once())
            ->method('getType')
            ->will($this->returnValue('manyToMany'));
        $this->configManager->expects($this->once())
            ->method('changeFieldType')
            ->with(self::SOURCE_CLASS, $relationName, $newFieldType);

        $extendConfigProvider = $this->getConfigProviderMock();
        $extendConfigProvider->expects($this->once())
            ->method('getConfig')
            ->with(self::SOURCE_CLASS, $relationName)
            ->will($this->returnValue($extendFieldConfig));
        $this->configManager->expects($this->once())
            ->method('persist')
            ->with($this->identicalTo($extendFieldConfig));

        $this->configManager->expects($this->any())
            ->method('getProvider')
            ->will(
                $this->returnValueMap(
                    [
                        ['extend', $extendConfigProvider],
                    ]
                )
            );

        $this->builder->addManyToManyRelation(
            $extendConfig,
            self::TARGET_CLASS,
            $relationName,
            [$targetTitleFieldName],
            [$targetDetailedFieldName],
            [$targetGridFieldName],
            [],
            $newFieldType
        );

        $this->assertEquals($expectedExtendFieldConfig, $extendFieldConfig);
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testAddManyToOneRelationForAlreadyExistRelationWithOptions()
    {
        $relationName    = 'testRelation';
        $relationKey     = 'manyToOne|Test\SourceEntity|Test\TargetEntity|testRelation';
        $targetFieldName = 'field1';

        $extendConfig = new Config(new EntityConfigId('extend', self::SOURCE_CLASS));
        $extendConfig->set(
            'relation',
            [
                $relationKey => []
            ]
        );
        $extendFieldConfig = new Config(new FieldConfigId('extend', self::SOURCE_CLASS, $relationName, 'manyToOne'));
        $testFieldConfig   = new Config(new FieldConfigId('test', self::SOURCE_CLASS, $relationName, 'manyToOne'));

        $expectedExtendFieldConfig = new Config($extendFieldConfig->getId());
        $expectedExtendFieldConfig->setValues(
            [
                'owner'         => ExtendScope::OWNER_CUSTOM,
                'is_extend'     => true,
                'relation_key'  => $relationKey,
                'target_entity' => self::TARGET_CLASS,
                'target_field'  => $targetFieldName
            ]
        );

        $expectedTestFieldConfig = new Config($testFieldConfig->getId());
        $expectedTestFieldConfig->setValues(
            [
                'test_attr' => 123
            ]
        );

        $fieldConfigModel = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel')
            ->disableOriginalConstructor()
            ->getMock();

        $this->configManager->expects($this->once())
            ->method('hasConfigFieldModel')
            ->with(self::SOURCE_CLASS, $relationName)
            ->will($this->returnValue(true));
        $this->configManager->expects($this->never())
            ->method('createConfigFieldModel');

        $this->configManager->expects($this->once())
            ->method('getConfigFieldModel')
            ->with(self::SOURCE_CLASS, $relationName)
            ->will($this->returnValue($fieldConfigModel));
        $fieldConfigModel->expects($this->once())
            ->method('getType')
            ->will($this->returnValue('manyToOne'));
        $this->configManager->expects($this->never())
            ->method('changeFieldType');

        $extendConfigProvider = $this->getConfigProviderMock();
        $extendConfigProvider->expects($this->once())
            ->method('getConfig')
            ->with(self::SOURCE_CLASS, $relationName)
            ->will($this->returnValue($extendFieldConfig));
        $this->configManager->expects($this->at(3))
            ->method('persist')
            ->with($this->identicalTo($extendFieldConfig));

        $testConfigProvider = $this->getConfigProviderMock();
        $testConfigProvider->expects($this->once())
            ->method('getConfig')
            ->with(self::SOURCE_CLASS, $relationName)
            ->will($this->returnValue($testFieldConfig));
        $this->configManager->expects($this->at(5))
            ->method('persist')
            ->with($this->identicalTo($testFieldConfig));

        $this->configManager->expects($this->any())
            ->method('getProvider')
            ->will(
                $this->returnValueMap(
                    [
                        ['extend', $extendConfigProvider],
                        ['test', $testConfigProvider],
                    ]
                )
            );

        $this->builder->addManyToOneRelation(
            $extendConfig,
            self::TARGET_CLASS,
            $relationName,
            $targetFieldName,
            [
                'extend' => [
                    'owner' => ExtendScope::OWNER_CUSTOM
                ],
                'test'   => [
                    'test_attr' => 123
                ]
            ]
        );

        $this->assertEquals($expectedExtendFieldConfig, $extendFieldConfig);
        $this->assertEquals($expectedTestFieldConfig, $testFieldConfig);
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testAddManyToManyRelationForAlreadyExistRelationWithOptions()
    {
        $relationName            = 'testRelation';
        $relationKey             = 'manyToMany|Test\SourceEntity|Test\TargetEntity|testRelation';
        $targetTitleFieldName    = 'field1';
        $targetDetailedFieldName = 'field2';
        $targetGridFieldName     = 'field3';

        $extendConfig = new Config(new EntityConfigId('extend', self::SOURCE_CLASS));
        $extendConfig->set(
            'relation',
            [
                $relationKey => []
            ]
        );
        $extendFieldConfig = new Config(new FieldConfigId('extend', self::SOURCE_CLASS, $relationName, 'manyToMany'));
        $testFieldConfig   = new Config(new FieldConfigId('test', self::SOURCE_CLASS, $relationName, 'manyToOne'));

        $expectedExtendFieldConfig = new Config($extendFieldConfig->getId());
        $expectedExtendFieldConfig->setValues(
            [
                'owner'           => ExtendScope::OWNER_CUSTOM,
                'is_extend'       => true,
                'relation_key'    => $relationKey,
                'target_entity'   => self::TARGET_CLASS,
                'target_title'    => [$targetTitleFieldName],
                'target_detailed' => [$targetDetailedFieldName],
                'target_grid'     => [$targetGridFieldName],
            ]
        );

        $expectedTestFieldConfig = new Config($testFieldConfig->getId());
        $expectedTestFieldConfig->setValues(
            [
                'test_attr' => 123
            ]
        );

        $fieldConfigModel = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel')
            ->disableOriginalConstructor()
            ->getMock();

        $this->configManager->expects($this->once())
            ->method('hasConfigFieldModel')
            ->with(self::SOURCE_CLASS, $relationName)
            ->will($this->returnValue(true));
        $this->configManager->expects($this->never())
            ->method('createConfigFieldModel');

        $this->configManager->expects($this->once())
            ->method('getConfigFieldModel')
            ->with(self::SOURCE_CLASS, $relationName)
            ->will($this->returnValue($fieldConfigModel));
        $fieldConfigModel->expects($this->once())
            ->method('getType')
            ->will($this->returnValue('manyToMany'));
        $this->configManager->expects($this->never())
            ->method('changeFieldType');

        $extendConfigProvider = $this->getConfigProviderMock();
        $extendConfigProvider->expects($this->once())
            ->method('getConfig')
            ->with(self::SOURCE_CLASS, $relationName)
            ->will($this->returnValue($extendFieldConfig));
        $this->configManager->expects($this->at(3))
            ->method('persist')
            ->with($this->identicalTo($extendFieldConfig));

        $testConfigProvider = $this->getConfigProviderMock();
        $testConfigProvider->expects($this->once())
            ->method('getConfig')
            ->with(self::SOURCE_CLASS, $relationName)
            ->will($this->returnValue($testFieldConfig));
        $this->configManager->expects($this->at(5))
            ->method('persist')
            ->with($this->identicalTo($testFieldConfig));

        $this->configManager->expects($this->any())
            ->method('getProvider')
            ->will(
                $this->returnValueMap(
                    [
                        ['extend', $extendConfigProvider],
                        ['test', $testConfigProvider],
                    ]
                )
            );

        $this->builder->addManyToManyRelation(
            $extendConfig,
            self::TARGET_CLASS,
            $relationName,
            [$targetTitleFieldName],
            [$targetDetailedFieldName],
            [$targetGridFieldName],
            [
                'extend' => [
                    'owner' => ExtendScope::OWNER_CUSTOM
                ],
                'test'   => [
                    'test_attr' => 123
                ]
            ]
        );

        $this->assertEquals($expectedExtendFieldConfig, $extendFieldConfig);
        $this->assertEquals($expectedTestFieldConfig, $testFieldConfig);
    }

    public function testAddManyToOneRelationTargetSide()
    {
        $relationName = 'testRelation';
        $relationKey  = 'manyToOne|Test\SourceEntity|Test\TargetEntity|testRelation';

        $extendConfig = new Config(new EntityConfigId('extend', self::TARGET_CLASS));

        $expectedExtendConfig = new Config(new EntityConfigId('extend', self::TARGET_CLASS));
        $expectedExtendConfig->set(
            'relation',
            [
                $relationKey => [
                    'target_field_id' => new FieldConfigId('extend', self::SOURCE_CLASS, $relationName, 'manyToOne'),
                    'owner'           => false,
                    'target_entity'   => self::SOURCE_CLASS,
                    'field_id'        => false
                ]
            ]
        );

        $extendConfigProvider = $this->getConfigProviderMock();
        $extendConfigProvider->expects($this->once())
            ->method('getConfig')
            ->with(self::TARGET_CLASS)
            ->will($this->returnValue($extendConfig));
        $this->configManager->expects($this->once())
            ->method('persist')
            ->with($this->identicalTo($extendConfig));

        $this->configManager->expects($this->any())
            ->method('getProvider')
            ->will(
                $this->returnValueMap(
                    [
                        ['extend', $extendConfigProvider]
                    ]
                )
            );

        $this->builder->addManyToOneRelationTargetSide(
            self::TARGET_CLASS,
            self::SOURCE_CLASS,
            $relationName,
            $relationKey
        );

        $this->assertEquals($expectedExtendConfig, $extendConfig);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    protected function getConfigProviderMock()
    {
        return $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider')
            ->disableOriginalConstructor()
            ->getMock();
    }
}
