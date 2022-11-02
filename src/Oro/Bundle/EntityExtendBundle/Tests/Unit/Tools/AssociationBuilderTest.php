<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\Tools;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\MappingException as ORMMappingException;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\Mapping\MappingException as PersistenceMappingException;
use Oro\Bundle\EntityConfigBundle\Config\Config;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Config\Id\EntityConfigId;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\EntityExtendBundle\Extend\RelationType;
use Oro\Bundle\EntityExtendBundle\Tools\AssociationBuilder;
use Oro\Bundle\EntityExtendBundle\Tools\RelationBuilder;
use Oro\Component\Testing\ReflectionUtil;

class AssociationBuilderTest extends \PHPUnit\Framework\TestCase
{
    private const SOURCE_CLASS = 'Test\SourceEntity';
    private const TARGET_CLASS = 'Test\TargetEntity';

    /** @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject */
    private $doctrine;

    /** @var ConfigManager|\PHPUnit\Framework\MockObject\MockObject */
    private $configManager;

    /** @var RelationBuilder|\PHPUnit\Framework\MockObject\MockObject */
    private $relationBuilder;

    protected function setUp(): void
    {
        $this->doctrine = $this->createMock(ManagerRegistry::class);
        $this->configManager = $this->createMock(ConfigManager::class);
        $this->relationBuilder = $this->createMock(RelationBuilder::class);
    }

    public function testCreateManyToManyRelationForNewAssociation()
    {
        $builder = $this->getMockBuilder(AssociationBuilder::class)
            ->onlyMethods(['getPrimaryKeyColumnNames'])
            ->setConstructorArgs([$this->doctrine, $this->configManager, $this->relationBuilder])
            ->getMock();

        $fieldName = 'target_entity_98c95332';

        $sourceEntityExtendConfig = new Config(new EntityConfigId('extend', self::SOURCE_CLASS));
        $targetEntityConfig = new Config(new EntityConfigId('entity', self::TARGET_CLASS));
        $targetEntityConfig->set('label', 'targetentity.label');
        $targetEntityConfig->set('plural_label', 'targetentity.plural_label');
        $targetEntityConfig->set('description', 'targetentity.description');
        $fieldExtendConfig = new Config(
            new FieldConfigId('extend', self::SOURCE_CLASS, $fieldName, RelationType::MANY_TO_MANY),
            ['state' => ExtendScope::STATE_NEW]
        );

        $extendConfigProvider = $this->createMock(ConfigProvider::class);
        $extendConfigProvider->expects($this->exactly(2))
            ->method('getConfig')
            ->willReturnMap([
                [self::SOURCE_CLASS, null, $sourceEntityExtendConfig],
                [self::SOURCE_CLASS, $fieldName, $fieldExtendConfig]
            ]);

        $entityConfigProvider = $this->createMock(ConfigProvider::class);
        $entityConfigProvider->expects($this->once())
            ->method('getConfig')
            ->with(self::TARGET_CLASS)
            ->willReturn($targetEntityConfig);

        $this->configManager->expects($this->exactly(2))
            ->method('getProvider')
            ->willReturnMap([
                ['extend', $extendConfigProvider],
                ['entity', $entityConfigProvider]
            ]);

        $builder->expects($this->once())
            ->method('getPrimaryKeyColumnNames')
            ->with(self::TARGET_CLASS)
            ->willReturn(['id']);

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
        $builder = $this->getMockBuilder(AssociationBuilder::class)
            ->onlyMethods(['getPrimaryKeyColumnNames'])
            ->setConstructorArgs([$this->doctrine, $this->configManager, $this->relationBuilder])
            ->getMock();

        $fieldName = 'target_entity_98c95332';

        $sourceEntityExtendConfig = new Config(new EntityConfigId('extend', self::SOURCE_CLASS));
        $targetEntityConfig = new Config(new EntityConfigId('entity', self::TARGET_CLASS));
        $fieldExtendConfig = new Config(
            new FieldConfigId('extend', self::SOURCE_CLASS, $fieldName, RelationType::MANY_TO_MANY),
            ['state' => ExtendScope::STATE_NEW]
        );

        $extendConfigProvider = $this->createMock(ConfigProvider::class);
        $extendConfigProvider->expects($this->exactly(2))
            ->method('getConfig')
            ->willReturnMap([
                [self::SOURCE_CLASS, null, $sourceEntityExtendConfig],
                [self::SOURCE_CLASS, $fieldName, $fieldExtendConfig]
            ]);

        $entityConfigProvider = $this->createMock(ConfigProvider::class);
        $entityConfigProvider->expects($this->once())
            ->method('getConfig')
            ->with(self::TARGET_CLASS)
            ->willReturn($targetEntityConfig);

        $this->configManager->expects($this->exactly(2))
            ->method('getProvider')
            ->willReturnMap([
                ['extend', $extendConfigProvider],
                ['entity', $entityConfigProvider]
            ]);

        $builder->expects($this->once())
            ->method('getPrimaryKeyColumnNames')
            ->with(self::TARGET_CLASS)
            ->willReturn(['id']);

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
        $builder = $this->getMockBuilder(AssociationBuilder::class)
            ->onlyMethods(['getPrimaryKeyColumnNames'])
            ->setConstructorArgs([$this->doctrine, $this->configManager, $this->relationBuilder])
            ->getMock();
        $fieldName = 'target_entity_98c95332';

        $sourceEntityExtendConfig = new Config(new EntityConfigId('extend', self::SOURCE_CLASS));
        $fieldExtendConfig = new Config(
            new FieldConfigId('extend', self::SOURCE_CLASS, $fieldName, RelationType::MANY_TO_MANY),
            ['state' => ExtendScope::STATE_ACTIVE]
        );

        $extendConfigProvider = $this->createMock(ConfigProvider::class);
        $extendConfigProvider->expects($this->exactly(2))
            ->method('getConfig')
            ->willReturnMap([
                [self::SOURCE_CLASS, null, $sourceEntityExtendConfig],
                [self::SOURCE_CLASS, $fieldName, $fieldExtendConfig]
            ]);

        $this->configManager->expects($this->once())
            ->method('getProvider')
            ->with('extend')
            ->willReturn($extendConfigProvider);

        $builder->expects($this->once())
            ->method('getPrimaryKeyColumnNames')
            ->with(self::TARGET_CLASS)
            ->willReturn(['id']);

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
        $builder = $this->getMockBuilder(AssociationBuilder::class)
            ->onlyMethods(['getPrimaryKeyColumnNames'])
            ->setConstructorArgs([$this->doctrine, $this->configManager, $this->relationBuilder])
            ->getMock();

        $fieldName = 'target_entity_98c95332';

        $sourceEntityExtendConfig = new Config(new EntityConfigId('extend', self::SOURCE_CLASS));
        $targetEntityConfig = new Config(new EntityConfigId('entity', self::TARGET_CLASS));
        $targetEntityConfig->set('label', 'targetentity.label');
        $targetEntityConfig->set('plural_label', 'targetentity.plural_label');
        $targetEntityConfig->set('description', 'targetentity.description');
        $fieldExtendConfig = new Config(
            new FieldConfigId('extend', self::SOURCE_CLASS, $fieldName, RelationType::MANY_TO_MANY),
            ['state' => ExtendScope::STATE_NEW]
        );

        $extendConfigProvider = $this->createMock(ConfigProvider::class);
        $extendConfigProvider->expects($this->exactly(2))
            ->method('getConfig')
            ->willReturnMap([
                [self::SOURCE_CLASS, null, $sourceEntityExtendConfig],
                [self::SOURCE_CLASS, $fieldName, $fieldExtendConfig]
            ]);

        $entityConfigProvider = $this->createMock(ConfigProvider::class);
        $entityConfigProvider->expects($this->once())
            ->method('getConfig')
            ->with(self::TARGET_CLASS)
            ->willReturn($targetEntityConfig);

        $this->configManager->expects($this->exactly(2))
            ->method('getProvider')
            ->willReturnMap([
                ['extend', $extendConfigProvider],
                ['entity', $entityConfigProvider]
            ]);

        $builder->expects($this->once())
            ->method('getPrimaryKeyColumnNames')
            ->with(self::TARGET_CLASS)
            ->willReturn(['id']);

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
        $builder = $this->getMockBuilder(AssociationBuilder::class)
            ->onlyMethods(['getPrimaryKeyColumnNames'])
            ->setConstructorArgs([$this->doctrine, $this->configManager, $this->relationBuilder])
            ->getMock();

        $fieldName = 'target_entity_98c95332';

        $sourceEntityExtendConfig = new Config(new EntityConfigId('extend', self::SOURCE_CLASS));
        $targetEntityConfig = new Config(new EntityConfigId('entity', self::TARGET_CLASS));
        $fieldExtendConfig = new Config(
            new FieldConfigId('extend', self::SOURCE_CLASS, $fieldName, RelationType::MANY_TO_MANY),
            ['state' => ExtendScope::STATE_NEW]
        );

        $extendConfigProvider = $this->createMock(ConfigProvider::class);
        $extendConfigProvider->expects($this->exactly(2))
            ->method('getConfig')
            ->willReturnMap([
                [self::SOURCE_CLASS, null, $sourceEntityExtendConfig],
                [self::SOURCE_CLASS, $fieldName, $fieldExtendConfig]
            ]);

        $entityConfigProvider = $this->createMock(ConfigProvider::class);
        $entityConfigProvider->expects($this->once())
            ->method('getConfig')
            ->with(self::TARGET_CLASS)
            ->willReturn($targetEntityConfig);

        $this->configManager->expects($this->exactly(2))
            ->method('getProvider')
            ->willReturnMap([
                ['extend', $extendConfigProvider],
                ['entity', $entityConfigProvider]
            ]);

        $builder->expects($this->once())
            ->method('getPrimaryKeyColumnNames')
            ->with(self::TARGET_CLASS)
            ->willReturn(['id']);

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
        $builder = $this->getMockBuilder(AssociationBuilder::class)
            ->onlyMethods(['getPrimaryKeyColumnNames'])
            ->setConstructorArgs([$this->doctrine, $this->configManager, $this->relationBuilder])
            ->getMock();

        $fieldName = 'target_entity_98c95332';

        $sourceEntityExtendConfig = new Config(new EntityConfigId('extend', self::SOURCE_CLASS));
        $fieldExtendConfig = new Config(
            new FieldConfigId('extend', self::SOURCE_CLASS, $fieldName, RelationType::MANY_TO_MANY),
            ['state' => ExtendScope::STATE_ACTIVE]
        );

        $extendConfigProvider = $this->createMock(ConfigProvider::class);
        $extendConfigProvider->expects($this->exactly(2))
            ->method('getConfig')
            ->willReturnMap([
                [self::SOURCE_CLASS, null, $sourceEntityExtendConfig],
                [self::SOURCE_CLASS, $fieldName, $fieldExtendConfig]
            ]);

        $this->configManager->expects($this->once())
            ->method('getProvider')
            ->with('extend')
            ->willReturn($extendConfigProvider);

        $builder->expects($this->once())
            ->method('getPrimaryKeyColumnNames')
            ->with(self::TARGET_CLASS)
            ->willReturn(['id']);

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

        $em = $this->createMock(EntityManager::class);
        $metadata = $this->createMock(ClassMetadata::class);

        $this->doctrine->expects($this->any())
            ->method('getManagerForClass')
            ->with($entityClass)
            ->willReturn($em);

        $em->expects($this->once())
            ->method('getClassMetadata')
            ->with($entityClass)
            ->willReturn($metadata);

        $metadata->expects($this->once())
            ->method('getIdentifierColumnNames')
            ->willReturn(['id', 'name']);

        $builder = new AssociationBuilder($this->doctrine, $this->configManager, $this->relationBuilder);
        $columnNames = ReflectionUtil::callMethod(
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
            ->willThrowException(new \ReflectionException('test'));

        $builder = new AssociationBuilder($this->doctrine, $this->configManager, $this->relationBuilder);
        $columnNames = ReflectionUtil::callMethod(
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
            ->willThrowException(new ORMMappingException('test'));

        $builder = new AssociationBuilder($this->doctrine, $this->configManager, $this->relationBuilder);
        $columnNames = ReflectionUtil::callMethod(
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
            ->willThrowException(new PersistenceMappingException('test'));

        $builder = new AssociationBuilder($this->doctrine, $this->configManager, $this->relationBuilder);
        $columnNames = ReflectionUtil::callMethod(
            $builder,
            'getPrimaryKeyColumnNames',
            ['Test']
        );

        $this->assertCount(1, $columnNames);
        $this->assertSame(['id'], $columnNames);
    }
}
