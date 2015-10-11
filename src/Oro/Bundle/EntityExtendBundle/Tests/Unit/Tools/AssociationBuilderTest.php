<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\Tools;

use Doctrine\ORM\Mapping\MappingException as ORMMappingException;
use Doctrine\Common\Persistence\Mapping\MappingException as PersistenceMappingException;

use Oro\Bundle\EntityConfigBundle\Config\Config;
use Oro\Bundle\EntityConfigBundle\Config\Id\EntityConfigId;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\EntityExtendBundle\Extend\RelationType;
use Oro\Bundle\EntityExtendBundle\Tools\AssociationBuilder;
use Oro\Bundle\EntityExtendBundle\Tests\Util\ReflectionUtil;

class AssociationBuilderTest extends \PHPUnit_Framework_TestCase
{
    const SOURCE_CLASS = 'Test\SourceEntity';
    const TARGET_CLASS = 'Test\TargetEntity';

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $doctrine;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $configManager;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $relationBuilder;

    public function setUp()
    {
        $this->doctrine        = $this->getMockBuilder('Doctrine\Common\Persistence\ManagerRegistry')
            ->disableOriginalConstructor()
            ->getMock();
        $this->configManager   = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Config\ConfigManager')
            ->disableOriginalConstructor()
            ->getMock();
        $this->relationBuilder = $this->getMockBuilder('Oro\Bundle\EntityExtendBundle\Tools\RelationBuilder')
            ->disableOriginalConstructor()
            ->getMock();
    }

    public function testCreateManyToManyRelationForNewAssociation()
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|AssociationBuilder $builder */
        $builder = $this->getMock(
            'Oro\Bundle\EntityExtendBundle\Tools\AssociationBuilder',
            ['getPrimaryKeyColumnNames'],
            [$this->doctrine, $this->configManager, $this->relationBuilder]
        );

        $fieldName = 'target_entity_98c95332';

        $sourceEntityExtendConfig = new Config(new EntityConfigId('extend', self::SOURCE_CLASS));
        $targetEntityConfig       = new Config(new EntityConfigId('entity', self::TARGET_CLASS));
        $targetEntityConfig->set('label', 'targetentity.label');
        $targetEntityConfig->set('plural_label', 'targetentity.plural_label');
        $targetEntityConfig->set('description', 'targetentity.description');
        $fieldExtendConfig = new Config(
            new FieldConfigId('extend', self::SOURCE_CLASS, $fieldName, RelationType::MANY_TO_MANY),
            ['state' => ExtendScope::STATE_NEW]
        );

        $extendConfigProvider = $this->getConfigProviderMock();
        $extendConfigProvider->expects($this->exactly(2))
            ->method('getConfig')
            ->will(
                $this->returnValueMap(
                    [
                        [self::SOURCE_CLASS, null, $sourceEntityExtendConfig],
                        [self::SOURCE_CLASS, $fieldName, $fieldExtendConfig]
                    ]
                )
            );

        $entityConfigProvider = $this->getConfigProviderMock();
        $entityConfigProvider->expects($this->once())
            ->method('getConfig')
            ->with(self::TARGET_CLASS)
            ->will($this->returnValue($targetEntityConfig));

        $this->configManager->expects($this->exactly(2))
            ->method('getProvider')
            ->will(
                $this->returnValueMap(
                    [
                        ['extend', $extendConfigProvider],
                        ['entity', $entityConfigProvider]
                    ]
                )
            );

        $builder->expects($this->once())
            ->method('getPrimaryKeyColumnNames')
            ->with(self::TARGET_CLASS)
            ->will($this->returnValue(['id']));

        $this->relationBuilder->expects($this->once())
            ->method('addManyToManyRelation')
            ->with(
                $this->identicalTo($sourceEntityExtendConfig),
                self::TARGET_CLASS,
                $fieldName,
                ['id'],
                ['id'],
                ['id']
            );
        $this->relationBuilder->expects($this->once())
            ->method('updateFieldConfigs')
            ->with(
                self::SOURCE_CLASS,
                $fieldName,
                [
                    'extend' => [
                        'without_default' => true,
                    ],
                    'entity' => [
                        'label'       => 'targetentity.plural_label',
                        'description' => 'targetentity.description',
                    ],
                    'view'   => [
                        'is_displayable' => true
                    ],
                    'form'   => [
                        'is_enabled' => true
                    ]
                ]
            );

        $builder->createManyToManyAssociation(self::SOURCE_CLASS, self::TARGET_CLASS, null);
    }

    public function testCreateManyToManyRelationForNewAssociationAndNoLabelForTargetEntity()
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|AssociationBuilder $builder */
        $builder = $this->getMock(
            'Oro\Bundle\EntityExtendBundle\Tools\AssociationBuilder',
            ['getPrimaryKeyColumnNames'],
            [$this->doctrine, $this->configManager, $this->relationBuilder]
        );

        $fieldName = 'target_entity_98c95332';

        $sourceEntityExtendConfig = new Config(new EntityConfigId('extend', self::SOURCE_CLASS));
        $targetEntityConfig       = new Config(new EntityConfigId('entity', self::TARGET_CLASS));
        $fieldExtendConfig        = new Config(
            new FieldConfigId('extend', self::SOURCE_CLASS, $fieldName, RelationType::MANY_TO_MANY),
            ['state' => ExtendScope::STATE_NEW]
        );

        $extendConfigProvider = $this->getConfigProviderMock();
        $extendConfigProvider->expects($this->exactly(2))
            ->method('getConfig')
            ->will(
                $this->returnValueMap(
                    [
                        [self::SOURCE_CLASS, null, $sourceEntityExtendConfig],
                        [self::SOURCE_CLASS, $fieldName, $fieldExtendConfig]
                    ]
                )
            );

        $entityConfigProvider = $this->getConfigProviderMock();
        $entityConfigProvider->expects($this->once())
            ->method('getConfig')
            ->with(self::TARGET_CLASS)
            ->will($this->returnValue($targetEntityConfig));

        $this->configManager->expects($this->exactly(2))
            ->method('getProvider')
            ->will(
                $this->returnValueMap(
                    [
                        ['extend', $extendConfigProvider],
                        ['entity', $entityConfigProvider]
                    ]
                )
            );

        $builder->expects($this->once())
            ->method('getPrimaryKeyColumnNames')
            ->with(self::TARGET_CLASS)
            ->will($this->returnValue(['id']));

        $this->relationBuilder->expects($this->once())
            ->method('addManyToManyRelation')
            ->with(
                $this->identicalTo($sourceEntityExtendConfig),
                self::TARGET_CLASS,
                $fieldName,
                ['id'],
                ['id'],
                ['id']
            );
        $this->relationBuilder->expects($this->once())
            ->method('updateFieldConfigs')
            ->with(
                self::SOURCE_CLASS,
                $fieldName,
                [
                    'extend' => [
                        'without_default' => true,
                    ],
                    'entity' => [
                        'label'       => 'test.sourceentity.' . $fieldName . '.plural_label',
                        'description' => 'test.sourceentity.' . $fieldName . '.description',
                    ],
                    'view'   => [
                        'is_displayable' => true
                    ],
                    'form'   => [
                        'is_enabled' => true
                    ]
                ]
            );

        $builder->createManyToManyAssociation(self::SOURCE_CLASS, self::TARGET_CLASS, null);
    }

    public function testCreateManyToManyRelationForExistingAssociation()
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|AssociationBuilder $builder */
        $builder = $this->getMock(
            'Oro\Bundle\EntityExtendBundle\Tools\AssociationBuilder',
            ['getPrimaryKeyColumnNames'],
            [$this->doctrine, $this->configManager, $this->relationBuilder]
        );

        $fieldName = 'target_entity_98c95332';

        $sourceEntityExtendConfig = new Config(new EntityConfigId('extend', self::SOURCE_CLASS));
        $fieldExtendConfig        = new Config(
            new FieldConfigId('extend', self::SOURCE_CLASS, $fieldName, RelationType::MANY_TO_MANY),
            ['state' => ExtendScope::STATE_ACTIVE]
        );

        $extendConfigProvider = $this->getConfigProviderMock();
        $extendConfigProvider->expects($this->exactly(2))
            ->method('getConfig')
            ->will(
                $this->returnValueMap(
                    [
                        [self::SOURCE_CLASS, null, $sourceEntityExtendConfig],
                        [self::SOURCE_CLASS, $fieldName, $fieldExtendConfig]
                    ]
                )
            );

        $this->configManager->expects($this->once())
            ->method('getProvider')
            ->with('extend')
            ->will($this->returnValue($extendConfigProvider));

        $builder->expects($this->once())
            ->method('getPrimaryKeyColumnNames')
            ->with(self::TARGET_CLASS)
            ->will($this->returnValue(['id']));

        $this->relationBuilder->expects($this->once())
            ->method('addManyToManyRelation')
            ->with(
                $this->identicalTo($sourceEntityExtendConfig),
                self::TARGET_CLASS,
                $fieldName,
                ['id'],
                ['id'],
                ['id']
            );
        $this->relationBuilder->expects($this->never())
            ->method('updateFieldConfigs');

        $builder->createManyToManyAssociation(self::SOURCE_CLASS, self::TARGET_CLASS, null);
    }

    public function testCreateManyToOneRelationForNewAssociation()
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|AssociationBuilder $builder */
        $builder = $this->getMock(
            'Oro\Bundle\EntityExtendBundle\Tools\AssociationBuilder',
            ['getPrimaryKeyColumnNames'],
            [$this->doctrine, $this->configManager, $this->relationBuilder]
        );

        $fieldName = 'target_entity_98c95332';

        $sourceEntityExtendConfig = new Config(new EntityConfigId('extend', self::SOURCE_CLASS));
        $targetEntityConfig       = new Config(new EntityConfigId('entity', self::TARGET_CLASS));
        $targetEntityConfig->set('label', 'targetentity.label');
        $targetEntityConfig->set('plural_label', 'targetentity.plural_label');
        $targetEntityConfig->set('description', 'targetentity.description');
        $fieldExtendConfig = new Config(
            new FieldConfigId('extend', self::SOURCE_CLASS, $fieldName, RelationType::MANY_TO_MANY),
            ['state' => ExtendScope::STATE_NEW]
        );

        $extendConfigProvider = $this->getConfigProviderMock();
        $extendConfigProvider->expects($this->exactly(2))
            ->method('getConfig')
            ->will(
                $this->returnValueMap(
                    [
                        [self::SOURCE_CLASS, null, $sourceEntityExtendConfig],
                        [self::SOURCE_CLASS, $fieldName, $fieldExtendConfig]
                    ]
                )
            );

        $entityConfigProvider = $this->getConfigProviderMock();
        $entityConfigProvider->expects($this->once())
            ->method('getConfig')
            ->with(self::TARGET_CLASS)
            ->will($this->returnValue($targetEntityConfig));

        $this->configManager->expects($this->exactly(2))
            ->method('getProvider')
            ->will(
                $this->returnValueMap(
                    [
                        ['extend', $extendConfigProvider],
                        ['entity', $entityConfigProvider]
                    ]
                )
            );

        $builder->expects($this->once())
            ->method('getPrimaryKeyColumnNames')
            ->with(self::TARGET_CLASS)
            ->will($this->returnValue(['id']));

        $this->relationBuilder->expects($this->once())
            ->method('addManyToOneRelation')
            ->with(
                $this->identicalTo($sourceEntityExtendConfig),
                self::TARGET_CLASS,
                $fieldName,
                'id'
            );
        $this->relationBuilder->expects($this->once())
            ->method('updateFieldConfigs')
            ->with(
                self::SOURCE_CLASS,
                $fieldName,
                [
                    'entity' => [
                        'label'       => 'targetentity.label',
                        'description' => 'targetentity.description',
                    ],
                    'view'   => [
                        'is_displayable' => false
                    ],
                    'form'   => [
                        'is_enabled' => false
                    ]
                ]
            );

        $builder->createManyToOneAssociation(self::SOURCE_CLASS, self::TARGET_CLASS, null);
    }

    public function testCreateManyToOneRelationForNewAssociationAndNoLabelForTargetEntity()
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|AssociationBuilder $builder */
        $builder = $this->getMock(
            'Oro\Bundle\EntityExtendBundle\Tools\AssociationBuilder',
            ['getPrimaryKeyColumnNames'],
            [$this->doctrine, $this->configManager, $this->relationBuilder]
        );

        $fieldName = 'target_entity_98c95332';

        $sourceEntityExtendConfig = new Config(new EntityConfigId('extend', self::SOURCE_CLASS));
        $targetEntityConfig       = new Config(new EntityConfigId('entity', self::TARGET_CLASS));
        $fieldExtendConfig        = new Config(
            new FieldConfigId('extend', self::SOURCE_CLASS, $fieldName, RelationType::MANY_TO_MANY),
            ['state' => ExtendScope::STATE_NEW]
        );

        $extendConfigProvider = $this->getConfigProviderMock();
        $extendConfigProvider->expects($this->exactly(2))
            ->method('getConfig')
            ->will(
                $this->returnValueMap(
                    [
                        [self::SOURCE_CLASS, null, $sourceEntityExtendConfig],
                        [self::SOURCE_CLASS, $fieldName, $fieldExtendConfig]
                    ]
                )
            );

        $entityConfigProvider = $this->getConfigProviderMock();
        $entityConfigProvider->expects($this->once())
            ->method('getConfig')
            ->with(self::TARGET_CLASS)
            ->will($this->returnValue($targetEntityConfig));

        $this->configManager->expects($this->exactly(2))
            ->method('getProvider')
            ->will(
                $this->returnValueMap(
                    [
                        ['extend', $extendConfigProvider],
                        ['entity', $entityConfigProvider]
                    ]
                )
            );

        $builder->expects($this->once())
            ->method('getPrimaryKeyColumnNames')
            ->with(self::TARGET_CLASS)
            ->will($this->returnValue(['id']));

        $this->relationBuilder->expects($this->once())
            ->method('addManyToOneRelation')
            ->with(
                $this->identicalTo($sourceEntityExtendConfig),
                self::TARGET_CLASS,
                $fieldName,
                'id'
            );
        $this->relationBuilder->expects($this->once())
            ->method('updateFieldConfigs')
            ->with(
                self::SOURCE_CLASS,
                $fieldName,
                [
                    'entity' => [
                        'label'       => 'test.sourceentity.' . $fieldName . '.label',
                        'description' => 'test.sourceentity.' . $fieldName . '.description',
                    ],
                    'view'   => [
                        'is_displayable' => false
                    ],
                    'form'   => [
                        'is_enabled' => false
                    ]
                ]
            );

        $builder->createManyToOneAssociation(self::SOURCE_CLASS, self::TARGET_CLASS, null);
    }

    public function testCreateManyToOneRelationForExistingAssociation()
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|AssociationBuilder $builder */
        $builder = $this->getMock(
            'Oro\Bundle\EntityExtendBundle\Tools\AssociationBuilder',
            ['getPrimaryKeyColumnNames'],
            [$this->doctrine, $this->configManager, $this->relationBuilder]
        );

        $fieldName = 'target_entity_98c95332';

        $sourceEntityExtendConfig = new Config(new EntityConfigId('extend', self::SOURCE_CLASS));
        $fieldExtendConfig        = new Config(
            new FieldConfigId('extend', self::SOURCE_CLASS, $fieldName, RelationType::MANY_TO_MANY),
            ['state' => ExtendScope::STATE_ACTIVE]
        );

        $extendConfigProvider = $this->getConfigProviderMock();
        $extendConfigProvider->expects($this->exactly(2))
            ->method('getConfig')
            ->will(
                $this->returnValueMap(
                    [
                        [self::SOURCE_CLASS, null, $sourceEntityExtendConfig],
                        [self::SOURCE_CLASS, $fieldName, $fieldExtendConfig]
                    ]
                )
            );

        $this->configManager->expects($this->once())
            ->method('getProvider')
            ->with('extend')
            ->will($this->returnValue($extendConfigProvider));

        $builder->expects($this->once())
            ->method('getPrimaryKeyColumnNames')
            ->with(self::TARGET_CLASS)
            ->will($this->returnValue(['id']));

        $this->relationBuilder->expects($this->once())
            ->method('addManyToOneRelation')
            ->with(
                $this->identicalTo($sourceEntityExtendConfig),
                self::TARGET_CLASS,
                $fieldName,
                'id'
            );
        $this->relationBuilder->expects($this->never())
            ->method('updateFieldConfigs');

        $builder->createManyToOneAssociation(self::SOURCE_CLASS, self::TARGET_CLASS, null);
    }

    public function testPrimaryKeyColumnNames()
    {
        $entityClass = 'Test\Entity';

        $em       = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $metadata = $this->getMockBuilder('Doctrine\ORM\Mapping\ClassMetadata')
            ->disableOriginalConstructor()
            ->getMock();

        $this->doctrine->expects($this->any())
            ->method('getManagerForClass')
            ->with($entityClass)
            ->willReturn($em);

        $em->expects($this->once())
            ->method('getClassMetadata')
            ->with($entityClass)
            ->will($this->returnValue($metadata));

        $metadata->expects($this->once())
            ->method('getIdentifierColumnNames')
            ->will($this->returnValue(['id', 'name']));

        $builder     = new AssociationBuilder($this->doctrine, $this->configManager, $this->relationBuilder);
        $columnNames = ReflectionUtil::callProtectedMethod(
            $builder,
            'getPrimaryKeyColumnNames',
            [$entityClass]
        );

        $this->assertCount(2, $columnNames);
        $this->assertSame(['id', 'name'], $columnNames);
    }

    public function testPrimaryKeyColumnNamesWithReflectionException()
    {
        $this->doctrine->expects($this->any())
            ->method('getManagerForClass')
            ->will($this->throwException(new \ReflectionException('test')));

        $builder     = new AssociationBuilder($this->doctrine, $this->configManager, $this->relationBuilder);
        $columnNames = ReflectionUtil::callProtectedMethod(
            $builder,
            'getPrimaryKeyColumnNames',
            ['Test']
        );

        $this->assertCount(1, $columnNames);
        $this->assertSame(['id'], $columnNames);
    }

    public function testPrimaryKeyColumnNamesWithORMMappingException()
    {
        $this->doctrine->expects($this->any())
            ->method('getManagerForClass')
            ->will($this->throwException(new ORMMappingException('test')));

        $builder     = new AssociationBuilder($this->doctrine, $this->configManager, $this->relationBuilder);
        $columnNames = ReflectionUtil::callProtectedMethod(
            $builder,
            'getPrimaryKeyColumnNames',
            ['Test']
        );

        $this->assertCount(1, $columnNames);
        $this->assertSame(['id'], $columnNames);
    }

    public function testPrimaryKeyColumnNamesWithPersistenceMappingException()
    {
        $this->doctrine->expects($this->any())
            ->method('getManagerForClass')
            ->will($this->throwException(new PersistenceMappingException('test')));

        $builder     = new AssociationBuilder($this->doctrine, $this->configManager, $this->relationBuilder);
        $columnNames = ReflectionUtil::callProtectedMethod(
            $builder,
            'getPrimaryKeyColumnNames',
            ['Test']
        );

        $this->assertCount(1, $columnNames);
        $this->assertSame(['id'], $columnNames);
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
