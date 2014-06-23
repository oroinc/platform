<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\Tools;

use Oro\Bundle\EntityConfigBundle\Config\Config;
use Oro\Bundle\EntityConfigBundle\Config\Id\EntityConfigId;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityExtendBundle\Tools\AssociationBuilder;
use Oro\Bundle\EntityExtendBundle\Tests\Util\ReflectionUtil;

class AssociationBuilderTest extends \PHPUnit_Framework_TestCase
{
    const SOURCE_CLASS = 'Test\SourceEntity';
    const TARGET_CLASS = 'Test\TargetEntity';

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $configManager;

    public function setUp()
    {
        $this->configManager = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Config\ConfigManager')
            ->disableOriginalConstructor()
            ->getMock();
    }

    public function testCreateManyToManyRelation()
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|AssociationBuilder $builder */
        $builder = $this->getMock(
            'Oro\Bundle\EntityExtendBundle\Tools\AssociationBuilder',
            [
                'getPrimaryKeyColumnNames',
                'addFieldConfig',
                'addManyToManyRelation'
            ],
            [$this->configManager]
        );

        $targetEntityConfig = new Config(new EntityConfigId('entity', self::TARGET_CLASS));

        $entityConfigProvider = $this->getConfigProviderMock();
        $entityConfigProvider->expects($this->once())
            ->method('getConfig')
            ->with(self::TARGET_CLASS)
            ->will($this->returnValue($targetEntityConfig));

        $this->configManager->expects($this->any())
            ->method('getProvider')
            ->will(
                $this->returnValueMap(
                    [
                        ['entity', $entityConfigProvider]
                    ]
                )
            );

        $builder->expects($this->once())
            ->method('getPrimaryKeyColumnNames')
            ->with(self::TARGET_CLASS)
            ->will($this->returnValue(['id']));

        $builder->expects($this->once())
            ->method('addFieldConfig')
            ->with(
                self::SOURCE_CLASS,
                'target_entity_98c95332',
                'manyToMany',
                [
                    'extend' => [
                        'owner'           => 'System',
                        'state'           => 'New',
                        'extend'          => true,
                        'without_default' => true,
                        'relation_key'    => 'manyToMany|Test\SourceEntity|Test\TargetEntity|target_entity_98c95332',
                        'target_entity'   => self::TARGET_CLASS,
                        'target_grid'     => ['id'],
                        'target_title'    => ['id'],
                        'target_detailed' => ['id'],
                    ],
                    'entity' => [
                        'label'       => 'test.targetentity.target_entity_98c95332.label',
                        'description' => 'test.targetentity.target_entity_98c95332.description',
                    ],
                    'view'   => [
                        'is_displayable' => true
                    ],
                    'form'   => [
                        'is_enabled' => true
                    ]
                ]
            );

        $builder->expects($this->once())
            ->method('addManyToManyRelation')
            ->with(
                self::TARGET_CLASS,
                self::SOURCE_CLASS,
                'target_entity_98c95332',
                'manyToMany|Test\SourceEntity|Test\TargetEntity|target_entity_98c95332'
            );

        $builder->createManyToManyAssociation(self::SOURCE_CLASS, self::TARGET_CLASS);
    }

    public function testCreateManyToOneRelation()
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|AssociationBuilder $builder */
        $builder = $this->getMock(
            'Oro\Bundle\EntityExtendBundle\Tools\AssociationBuilder',
            [
                'getPrimaryKeyColumnNames',
                'addFieldConfig',
                'addManyToOneRelation',
                'addManyToOneRelationTargetSide'
            ],
            [$this->configManager]
        );

        $targetEntityConfig = new Config(new EntityConfigId('entity', self::TARGET_CLASS));

        $entityConfigProvider = $this->getConfigProviderMock();
        $entityConfigProvider->expects($this->once())
            ->method('getConfig')
            ->with(self::TARGET_CLASS)
            ->will($this->returnValue($targetEntityConfig));

        $this->configManager->expects($this->any())
            ->method('getProvider')
            ->will(
                $this->returnValueMap(
                    [
                        ['entity', $entityConfigProvider]
                    ]
                )
            );

        $builder->expects($this->once())
            ->method('getPrimaryKeyColumnNames')
            ->with(self::TARGET_CLASS)
            ->will($this->returnValue(['id']));

        $builder->expects($this->once())
            ->method('addFieldConfig')
            ->with(
                self::SOURCE_CLASS,
                'target_entity_98c95332',
                'manyToOne',
                [
                    'extend' => [
                        'owner'         => 'System',
                        'state'         => 'New',
                        'extend'        => true,
                        'target_entity' => self::TARGET_CLASS,
                        'target_field'  => 'id',
                        'relation_key'  => 'manyToOne|Test\SourceEntity|Test\TargetEntity|target_entity_98c95332',
                    ],
                    'entity' => [
                        'label'       => 'test.targetentity.target_entity_98c95332.label',
                        'description' => 'test.targetentity.target_entity_98c95332.description',
                    ],
                    'view'   => [
                        'is_displayable' => false
                    ],
                    'form'   => [
                        'is_enabled' => false
                    ]
                ]
            );

        $builder->expects($this->once())
            ->method('addManyToOneRelation')
            ->with(
                self::TARGET_CLASS,
                self::SOURCE_CLASS,
                'target_entity_98c95332',
                'manyToOne|Test\SourceEntity|Test\TargetEntity|target_entity_98c95332'
            );

        $builder->expects($this->once())
            ->method('addManyToOneRelationTargetSide')
            ->with(
                self::TARGET_CLASS,
                self::SOURCE_CLASS,
                'target_entity_98c95332',
                'manyToOne|Test\SourceEntity|Test\TargetEntity|target_entity_98c95332'
            );

        $builder->createManyToOneAssociation(self::SOURCE_CLASS, self::TARGET_CLASS);
    }

    public function testPrimaryKeyColumnNames()
    {
        $entityClass = 'Test\Entity';

        $emMock       = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $metadataMock = $this->getMockBuilder('Doctrine\ORM\Mapping\ClassMetadata')
            ->disableOriginalConstructor()
            ->getMock();

        $emMock->expects($this->once())
            ->method('getClassMetadata')
            ->with($entityClass)
            ->will($this->returnValue($metadataMock));

        $metadataMock->expects($this->once())
            ->method('getIdentifierColumnNames')
            ->will($this->returnValue(['id', 'name']));

        $this->configManager->expects($this->once())
            ->method('getEntityManager')
            ->will($this->returnValue($emMock));

        $builder     = new AssociationBuilder($this->configManager);
        $columnNames = ReflectionUtil::callProtectedMethod(
            $builder,
            'getPrimaryKeyColumnNames',
            [$entityClass]
        );

        $this->assertCount(2, $columnNames);
        $this->assertSame(['id', 'name'], $columnNames);
    }

    public function testPrimaryKeyColumnNamesException()
    {
        $this->configManager->expects($this->once())
            ->method('getEntityManager')
            ->will($this->throwException(new \ReflectionException('test')));

        $builder     = new AssociationBuilder($this->configManager);
        $columnNames = ReflectionUtil::callProtectedMethod(
            $builder,
            'getPrimaryKeyColumnNames',
            ['Test']
        );

        $this->assertCount(1, $columnNames);
        $this->assertSame(['id'], $columnNames);
    }

    public function testAddFieldConfig()
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|AssociationBuilder $builder */
        $builder = $this->getMock(
            'Oro\Bundle\EntityExtendBundle\Tools\AssociationBuilder',
            [],
            [$this->configManager]
        );

        $this->configManager->expects($this->once())
            ->method('createConfigFieldModel')
            ->with('Test\Entity', 'testField', 'manyToOne');

        $fieldConfig = $this->getMock('Oro\Bundle\EntityConfigBundle\Config\ConfigInterface');
        $fieldConfig->expects($this->once())
            ->method('set')
            ->with('test', true);

        $extendConfigProvider = $this->getConfigProviderMock();
        $extendConfigProvider->expects($this->once())
            ->method('getConfig')
            ->with('Test\Entity', 'testField')
            ->will($this->returnValue($fieldConfig));
        $extendConfigProvider->expects($this->once())
            ->method('persist')
            ->with($fieldConfig);

        $this->configManager->expects($this->any())
            ->method('getProvider')
            ->will(
                $this->returnValueMap(
                    [
                        ['extend', $extendConfigProvider]
                    ]
                )
            );
        $this->configManager->expects($this->once())
            ->method('calculateConfigChangeSet')
            ->with($fieldConfig);

        ReflectionUtil::callProtectedMethod(
            $builder,
            'addFieldConfig',
            ['Test\Entity', 'testField', 'manyToOne', ['extend' => ['test' => true]]]
        );
    }

    public function testAddManyToOneRelation()
    {
        $relationName = 'testRelation';
        $relationKey  = 'manyToOne|Test\SourceEntity|Test\TargetEntity|testRelation';

        $extendConfig = new Config(new EntityConfigId('extend', self::SOURCE_CLASS));

        $expectedExtendConfig = new Config(new EntityConfigId('extend', self::SOURCE_CLASS));
        $expectedExtendConfig->set(
            'relation',
            [
                $relationKey => [
                    'assign'          => false,
                    'field_id'        => new FieldConfigId('extend', self::SOURCE_CLASS, $relationName, 'manyToOne'),
                    'owner'           => true,
                    'target_entity'   => self::TARGET_CLASS,
                    'target_field_id' => false
                ]
            ]
        );
        $expectedExtendConfig->set(
            'schema',
            ['relation' => [$relationName => $relationName]]
        );
        $expectedExtendConfig->set(
            'index',
            [$relationName => null]
        );

        $extendConfigProvider = $this->getConfigProviderMock();
        $extendConfigProvider->expects($this->once())
            ->method('getConfig')
            ->with(self::SOURCE_CLASS)
            ->will($this->returnValue($extendConfig));
        $extendConfigProvider->expects($this->once())
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
        $this->configManager->expects($this->once())
            ->method('calculateConfigChangeSet')
            ->with($this->identicalTo($extendConfig));

        $builder = new AssociationBuilder($this->configManager);
        ReflectionUtil::callProtectedMethod(
            $builder,
            'addManyToOneRelation',
            [self::TARGET_CLASS, self::SOURCE_CLASS, $relationName, $relationKey]
        );

        $this->assertEquals($expectedExtendConfig, $extendConfig);
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
                    'assign'          => false,
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
        $extendConfigProvider->expects($this->once())
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
        $this->configManager->expects($this->once())
            ->method('calculateConfigChangeSet')
            ->with($this->identicalTo($extendConfig));

        $builder = new AssociationBuilder($this->configManager);
        ReflectionUtil::callProtectedMethod(
            $builder,
            'addManyToOneRelationTargetSide',
            [self::TARGET_CLASS, self::SOURCE_CLASS, $relationName, $relationKey]
        );

        $this->assertEquals($expectedExtendConfig, $extendConfig);
    }

    public function testAddManyToManyRelation()
    {
        $relationName = 'testRelation';
        $relationKey  = 'manyToOne|Test\SourceEntity|Test\TargetEntity|testRelation';

        $extendConfig = new Config(new EntityConfigId('extend', self::TARGET_CLASS));

        $expectedExtendConfig = new Config(new EntityConfigId('extend', self::TARGET_CLASS));
        $expectedExtendConfig->set(
            'relation',
            [
                $relationKey => [
                    'assign'          => false,
                    'field_id'        => new FieldConfigId('extend', self::SOURCE_CLASS, $relationName, 'manyToMany'),
                    'owner'           => true,
                    'target_entity'   => self::TARGET_CLASS,
                    'target_field_id' => false,
                ]
            ]
        );
        $expectedExtendConfig->set('index', [$relationName => null]);

        $extendConfigProvider = $this->getConfigProviderMock();
        $extendConfigProvider->expects($this->once())
            ->method('getConfig')
            ->with(self::SOURCE_CLASS)
            ->will($this->returnValue($extendConfig));
        $extendConfigProvider->expects($this->once())
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
        $this->configManager->expects($this->once())
            ->method('calculateConfigChangeSet')
            ->with($this->identicalTo($extendConfig));

        $builder = new AssociationBuilder($this->configManager);
        ReflectionUtil::callProtectedMethod(
            $builder,
            'addManyToManyRelation',
            [self::TARGET_CLASS, self::SOURCE_CLASS, $relationName, $relationKey]
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
