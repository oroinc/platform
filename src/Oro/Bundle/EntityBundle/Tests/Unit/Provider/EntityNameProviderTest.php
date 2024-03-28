<?php

namespace Oro\Bundle\EntityBundle\Tests\Unit\Provider;

use Doctrine\Inflector\Rules\English\InflectorFactory;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\Mapping\ClassMetadata;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\EntityBundle\Provider\EntityNameProvider;
use Oro\Bundle\EntityBundle\Tests\Unit\ORM\Fixtures\TestEntity;
use Oro\Bundle\EntityBundle\Tests\Unit\ORM\Fixtures\TestEntityWithEnumField;
use Oro\Bundle\EntityBundle\Tests\Unit\ORM\Fixtures\TestEntityWithHiddenField;
use Oro\Bundle\EntityBundle\Tests\Unit\ORM\Fixtures\TestEntityWithMagicEnumField;
use Oro\Bundle\EntityBundle\Tests\Unit\ORM\Fixtures\TestEntityWithMagicHiddenField;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Tests\Unit\ConfigProviderMock;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class EntityNameProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var ClassMetadata|\PHPUnit\Framework\MockObject\MockObject */
    private $metadata;

    /** @var ConfigProviderMock */
    private $extendConfigProvider;

    /** @var EntityNameProvider */
    private $entityNameProvider;

    protected function setUp(): void
    {
        $this->metadata = $this->createMock(ClassMetadata::class);

        $doctrine = $this->createMock(ManagerRegistry::class);
        $manager = $this->createMock(ObjectManager::class);
        $doctrine->expects(self::any())
            ->method('getManagerForClass')
            ->willReturn($manager);
        $manager->expects(self::any())
            ->method('getClassMetadata')
            ->willReturn($this->metadata);

        $this->extendConfigProvider = new ConfigProviderMock($this->createMock(ConfigManager::class), 'extend');
        $this->entityNameProvider = new EntityNameProvider(
            ['firstName', 'name', 'title', 'subject'],
            $doctrine,
            $this->extendConfigProvider,
            (new InflectorFactory())->build()
        );
    }

    public function testGetNameForUnsupportedFormat()
    {
        $result = $this->entityNameProvider->getName('test', null, new TestEntity());
        self::assertFalse($result);
    }

    public function testGetName()
    {
        $entity = new TestEntity();
        $entity->setName('test');

        $this->metadata->expects(self::atLeastOnce())
            ->method('getName')
            ->willReturn(TestEntity::class);
        $this->metadata->expects(self::atLeastOnce())
            ->method('hasField')
            ->willReturnCallback(function ($name) {
                return 'name' === $name;
            });
        $this->metadata->expects(self::once())
            ->method('getTypeOfField')
            ->with('name')
            ->willReturn('string');

        $result = $this->entityNameProvider->getName('short', null, $entity);
        self::assertEquals('test', $result);
    }

    public function testGetNameForExtendedEntity()
    {
        $entity = new TestEntity();
        $entity->setName('test');
        $entity->setDescription('description');

        $this->initEntityFieldsMetadata(
            false,
            [
                'name' => [
                    'is_extend'  => true,
                    'is_deleted' => false
                ]
            ]
        );

        self::assertEquals(
            'test',
            $this->entityNameProvider->getName('short', null, $entity)
        );

        self::assertEquals(
            'test description',
            $this->entityNameProvider->getName('full', null, $entity)
        );

        $this->initEntityFieldsMetadata(
            false,
            [
                'name' => [
                    'is_extend'  => true,
                    'is_deleted' => true
                ]
            ]
        );

        self::assertFalse(
            $this->entityNameProvider->getName('short', null, $entity)
        );

        self::assertEquals(
            'description',
            $this->entityNameProvider->getName('full', null, $entity)
        );
    }

    public function testGetNameForNotManageableEntity()
    {
        $entity = new \stdClass();

        $result = $this->entityNameProvider->getName('short', null, $entity);
        self::assertFalse($result);
    }

    public function testGetNameNoAppropriateField()
    {
        $entity = new TestEntity();

        $result = $this->entityNameProvider->getName('short', null, $entity);
        self::assertFalse($result);
    }

    public function testGetNameWhenEmptyNameButHasIdentifier()
    {
        $entity = new TestEntity(123);

        $this->initEntityFieldsMetadata(true);

        $result = $this->entityNameProvider->getName('short', null, $entity);
        self::assertSame('123', $result);

        $result = $this->entityNameProvider->getName('full', null, $entity);
        self::assertSame('123', $result);
    }

    public function testGetNameForEntityWithHiddenField()
    {
        $entity = new TestEntityWithHiddenField(1, 'hidden');
        $entity->setName('test');

        $this->metadata->expects(self::atLeastOnce())
            ->method('getName')
            ->willReturn(TestEntityWithHiddenField::class);
        $this->metadata->expects(self::once())
            ->method('getFieldNames')
            ->willReturn(['id', 'name', 'hidden']);
        $this->metadata->expects(self::exactly(3))
            ->method('hasField')
            ->willReturnMap([
                ['id', true],
                ['name', true],
                ['hidden', true]
            ]);
        $this->metadata->expects(self::exactly(3))
            ->method('getTypeOfField')
            ->willReturnMap([
                ['id', 'integer'],
                ['name', 'string'],
                ['hidden', 'string']
            ]);

        $result = $this->entityNameProvider->getName('full', null, $entity);
        self::assertEquals('test', $result);
    }

    public function testGetNameForEntityWithEnumField()
    {
        $entity = new TestEntityWithEnumField(1);
        $entity->setName('enum');

        $this->metadata->expects(self::atLeastOnce())
            ->method('getName')
            ->willReturn(TestEntityWithEnumField::class);

        $this->metadata->expects(self::once())
            ->method('getFieldNames')
            ->willReturn(['id', 'name', 'enum']);

        $this->metadata->expects(self::exactly(3))
            ->method('hasField')
            ->willReturnMap([
                ['id', true],
                ['name', true],
                ['enum', true]
            ]);
        $this->metadata->expects(self::exactly(3))
            ->method('getTypeOfField')
            ->willReturnMap([
                ['id', 'integer'],
                ['name', 'string'],
                ['enum', 'string']
            ]);

        $result = $this->entityNameProvider->getName('full', null, $entity);

        self::assertEquals('enum', $result);
    }

    public function testGetNameForEntityWithEnumFieldThanHasAccessViaMagicMethods()
    {
        $entity = new TestEntityWithMagicEnumField(1);
        $entity->setName('enum');

        $this->metadata->expects(self::atLeastOnce())
            ->method('getName')
            ->willReturn(TestEntityWithMagicHiddenField::class);
        $this->metadata->expects(self::once())
            ->method('getFieldNames')
            ->willReturn(['id', 'name', 'enum']);
        $this->metadata->expects(self::exactly(3))
            ->method('hasField')
            ->willReturnMap([
                ['id', true],
                ['name', true],
                ['enum', true]
            ]);
        $this->metadata->expects(self::exactly(3))
            ->method('getTypeOfField')
            ->willReturnMap([
                ['id', 'integer'],
                ['name', 'string'],
                ['enum', 'string']
            ]);

        $result = $this->entityNameProvider->getName('full', null, $entity);
        self::assertEquals('enum Option1', $result);
    }

    public function testGetNameForEntityWithHiddenFieldThanHasAccessViaMagicMethods()
    {
        $entity = new TestEntityWithMagicHiddenField(1, 'hidden');
        $entity->setName('test');

        $this->metadata->expects(self::atLeastOnce())
            ->method('getName')
            ->willReturn(TestEntityWithMagicHiddenField::class);
        $this->metadata->expects(self::once())
            ->method('getFieldNames')
            ->willReturn(['id', 'name', 'hidden']);
        $this->metadata->expects(self::exactly(3))
            ->method('hasField')
            ->willReturnMap([
                ['id', true],
                ['name', true],
                ['hidden', true]
            ]);
        $this->metadata->expects(self::exactly(3))
            ->method('getTypeOfField')
            ->willReturnMap([
                ['id', 'integer'],
                ['name', 'string'],
                ['hidden', 'string']
            ]);

        $result = $this->entityNameProvider->getName('full', null, $entity);
        self::assertEquals('test hidden', $result);
    }

    public function testGetNameFullEmptyNameButNoIdentifier()
    {
        $entity = new TestEntity(123);
        $this->initEntityFieldsMetadata(false);

        $result = $this->entityNameProvider->getName('full', null, $entity);
        self::assertFalse($result);
    }

    public function testGetNameDQLForUnsupportedFormat()
    {
        $result = $this->entityNameProvider->getNameDQL('test', null, TestEntity::class, 'alias');
        self::assertFalse($result);
    }

    public function testGetNameDQLShortNoIdentifier()
    {
        $this->metadata->expects(self::atLeastOnce())
            ->method('getName')
            ->willReturn(TestEntity::class);
        $this->metadata->expects(self::atLeastOnce())
            ->method('hasField')
            ->willReturnCallback(function ($name) {
                return 'name' === $name;
            });
        $this->metadata->expects(self::once())
            ->method('getTypeOfField')
            ->with('name')
            ->willReturn('string');
        $this->metadata->expects(self::once())
            ->method('getIdentifierFieldNames')
            ->willReturn([]);

        $result = $this->entityNameProvider->getNameDQL('short', null, TestEntity::class, 'alias');
        self::assertEquals('alias.name', $result);
    }

    public function testGetNameDQLShortForExtendedEntity()
    {
        $this->initEntityFieldsMetadata(
            false,
            [
                'name' => [
                    'is_extend'  => true,
                    'is_deleted' => false
                ]
            ]
        );

        $shortFormatDQL = $this->entityNameProvider->getNameDQL('short', null, TestEntity::class, 'alias');
        self::assertEquals('alias.name', $shortFormatDQL);

        $this->initEntityFieldsMetadata(
            false,
            [
                'name' => [
                    'is_extend'  => true,
                    'is_deleted' => true
                ]
            ]
        );

        $shortFormatDQL = $this->entityNameProvider->getNameDQL('short', null, TestEntity::class, 'alias');
        self::assertFalse($shortFormatDQL);
    }

    public function testGetNameDQLShortWithIdentifier()
    {
        $this->initEntityFieldsMetadata(true);

        $result = $this->entityNameProvider->getNameDQL('short', null, TestEntity::class, 'alias');
        self::assertEquals('COALESCE(CAST(alias.name AS string), CAST(alias.id AS string))', $result);
    }

    public function testGetNameDQLForNotManageableEntity()
    {
        $result = $this->entityNameProvider->getNameDQL('short', null, 'Test\Class', 'alias');
        self::assertFalse($result);
    }

    public function testGetNameDQLNoAppropriateField()
    {
        $result = $this->entityNameProvider->getNameDQL('short', null, TestEntity::class, 'alias');
        self::assertFalse($result);
    }

    public function testGetNameDQLShortNoAppropriateField()
    {
        $result = $this->entityNameProvider->getNameDQL('short', null, TestEntity::class, 'alias');
        self::assertFalse($result);
    }

    public function testGetNameDQLFullNoAppropriateFields()
    {
        $this->metadata->expects(self::once())
            ->method('getFieldNames')
            ->willReturn([]);
        $result = $this->entityNameProvider->getNameDQL('full', null, TestEntity::class, 'alias');
        self::assertFalse($result);
    }

    public function testGetNameDQLFull()
    {
        $this->initEntityFieldsMetadata(true);

        $result = $this->entityNameProvider->getNameDQL('full', null, TestEntity::class, 'alias');
        self::assertEquals(
            'COALESCE(CAST(CONCAT_WS(\' \', alias.name, alias.description) AS string), CAST(alias.id AS string))',
            $result
        );
    }

    public function testGetNameDQLFullNoIdentifier()
    {
        $this->initEntityFieldsMetadata(false);

        $result = $this->entityNameProvider->getNameDQL('full', null, TestEntity::class, 'alias');
        self::assertEquals('CONCAT_WS(\' \', alias.name, alias.description)', $result);
    }

    public function testGetNameDQLFullForExtendedEntity()
    {
        $this->initEntityFieldsMetadata(
            false,
            [
                'name' => [
                    'is_extend'  => true,
                    'is_deleted' => false
                ]
            ]
        );

        $result = $this->entityNameProvider->getNameDQL('full', null, TestEntity::class, 'alias');
        self::assertEquals('CONCAT_WS(\' \', alias.name, alias.description)', $result);

        $this->initEntityFieldsMetadata(
            false,
            [
                'name' => [
                    'is_extend'  => true,
                    'is_deleted' => true
                ]
            ]
        );

        $result = $this->entityNameProvider->getNameDQL('full', null, TestEntity::class, 'alias');
        self::assertEquals('alias.description', $result);
    }

    public function testGetNameDQLForEntityWithHiddenField()
    {
        $this->metadata->expects(self::atLeastOnce())
            ->method('getName')
            ->willReturn(TestEntityWithHiddenField::class);
        $this->metadata->expects(self::once())
            ->method('getIdentifierFieldNames')
            ->willReturn(['id']);
        $this->metadata->expects(self::once())
            ->method('getFieldNames')
            ->willReturn(['id', 'name', 'hidden']);
        $this->metadata->expects(self::exactly(3))
            ->method('hasField')
            ->willReturnMap([
                ['id', true],
                ['name', true],
                ['hidden', true]
            ]);
        $this->metadata->expects(self::exactly(3))
            ->method('getTypeOfField')
            ->willReturnMap([
                ['id', 'integer'],
                ['name', 'string'],
                ['hidden', 'string']
            ]);

        $result = $this->entityNameProvider->getNameDQL('full', null, TestEntityWithHiddenField::class, 'alias');
        self::assertEquals(
            'COALESCE(CAST(CONCAT_WS(\' \', alias.name, alias.hidden) AS string), CAST(alias.id AS string))',
            $result
        );
    }

    public function testGetNameDQLForEntityWithEnumField()
    {
        $this->metadata->expects(self::atLeastOnce())
            ->method('getName')
            ->willReturn(TestEntityWithEnumField::class);
        $this->metadata->expects(self::once())
            ->method('getIdentifierFieldNames')
            ->willReturn(['id']);
        $this->metadata->expects(self::once())
            ->method('getFieldNames')
            ->willReturn(['id', 'name', 'enum']);
        $this->metadata->expects(self::exactly(3))
            ->method('hasField')
            ->willReturnMap([
                ['id', true],
                ['name', true],
                ['enum', true]
            ]);
        $this->metadata->expects(self::exactly(3))
            ->method('getTypeOfField')
            ->willReturnMap([
                ['id', 'integer'],
                ['name', 'string'],
                ['enum', 'string']
            ]);

        $result = $this->entityNameProvider->getNameDQL('full', null, TestEntityWithEnumField::class, 'alias');
        self::assertEquals(
            'COALESCE(CAST(CONCAT_WS(\' \', alias.name, alias.enum) AS string), CAST(alias.id AS string))',
            $result
        );
    }

    public function testGetNameDQLForEntityWithHiddenFieldThanHasAccessViaMagicMethods()
    {
        $this->metadata->expects(self::atLeastOnce())
            ->method('getName')
            ->willReturn(TestEntityWithMagicHiddenField::class);
        $this->metadata->expects(self::once())
            ->method('getIdentifierFieldNames')
            ->willReturn(['id']);
        $this->metadata->expects(self::once())
            ->method('getFieldNames')
            ->willReturn(['id', 'name', 'hidden']);
        $this->metadata->expects(self::exactly(3))
            ->method('hasField')
            ->willReturnMap([
                ['id', true],
                ['name', true],
                ['hidden', true]
            ]);
        $this->metadata->expects(self::exactly(3))
            ->method('getTypeOfField')
            ->willReturnMap([
                ['id', 'integer'],
                ['name', 'string'],
                ['hidden', 'string']
            ]);

        $result = $this->entityNameProvider->getNameDQL('full', null, TestEntityWithMagicHiddenField::class, 'alias');
        self::assertEquals(
            'COALESCE(CAST(CONCAT_WS(\' \', alias.name, alias.hidden) AS string), CAST(alias.id AS string))',
            $result
        );
    }

    public function testGetNameDQLForEntityWithEnumFieldThanHasAccessViaMagicMethods()
    {
        $this->metadata->expects(self::atLeastOnce())
            ->method('getName')
            ->willReturn(TestEntityWithMagicEnumField::class);
        $this->metadata->expects(self::once())
            ->method('getIdentifierFieldNames')
            ->willReturn(['id']);
        $this->metadata->expects(self::once())
            ->method('getFieldNames')
            ->willReturn(['id', 'name', 'enum']);
        $this->metadata->expects(self::exactly(3))
            ->method('hasField')
            ->willReturnMap([
                ['id', true],
                ['name', true],
                ['enum', true]
            ]);
        $this->metadata->expects(self::exactly(3))
            ->method('getTypeOfField')
            ->willReturnMap([
                ['id', 'integer'],
                ['name', 'string'],
                ['enum', 'string']
            ]);

        $result = $this->entityNameProvider->getNameDQL('full', null, TestEntityWithMagicEnumField::class, 'alias');
        self::assertEquals(
            'COALESCE(CAST(CONCAT_WS(\' \', alias.name, alias.enum) AS string), CAST(alias.id AS string))',
            $result
        );
    }

    private function initEntityFieldsMetadata(bool $initIdentityField, array $extendedFieldConfig = []): void
    {
        $this->metadata->expects(self::any())
            ->method('getIdentifierFieldNames')
            ->willReturn($initIdentityField ? ['id'] : []);

        $this->metadata->expects(self::any())
            ->method('hasField')
            ->willReturnMap([
                ['name', true],
                ['description', true]
            ]);

        $this->metadata->expects(self::any())
            ->method('getName')
            ->willReturn(TestEntity::class);

        $this->metadata->expects(self::any())
            ->method('getTypeOfField')
            ->willReturnMap([
                ['name', 'string'],
                ['description', 'string']
            ]);

        $this->metadata->expects(self::any())
            ->method('getFieldNames')
            ->willReturn(['name', 'description']);

        foreach ($extendedFieldConfig as $fieldName => $extendedConfig) {
            $this->extendConfigProvider->addFieldConfig(
                TestEntity::class,
                $fieldName,
                'string',
                $extendedConfig
            );
        }
    }
}
