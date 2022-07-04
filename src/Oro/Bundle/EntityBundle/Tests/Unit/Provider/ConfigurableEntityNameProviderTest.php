<?php

namespace Oro\Bundle\EntityBundle\Tests\Unit\Provider;

use Doctrine\Inflector\Rules\English\InflectorFactory;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\Mapping\ClassMetadata;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\EntityBundle\Provider\ConfigurableEntityNameProvider;
use Oro\Bundle\EntityBundle\Tests\Unit\ORM\Fixtures\TestEntity;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class ConfigurableEntityNameProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var ClassMetadata|\PHPUnit\Framework\MockObject\MockObject */
    private $metadata;

    /** @var ConfigurableEntityNameProvider */
    private $entityNameProvider;

    protected function setUp(): void
    {
        $this->metadata = $this->createMock(ClassMetadata::class);

        $doctrine = $this->createMock(ManagerRegistry::class);
        $manager = $this->createMock(ObjectManager::class);
        $doctrine->expects(self::any())
            ->method('getManagerForClass')
            ->willReturnMap([
                [TestEntity::class, $manager]
            ]);
        $manager->expects(self::any())
            ->method('getClassMetadata')
            ->willReturnMap([
                [TestEntity::class, $this->metadata]
            ]);

        $this->entityNameProvider = new ConfigurableEntityNameProvider(
            [TestEntity::class => ['full' => ['name', 'description'], 'short' => ['description']]],
            $doctrine,
            (new InflectorFactory())->build()
        );
    }

    public function testGetNameFullNotConfigured()
    {
        $entity = new \stdClass();

        $result = $this->entityNameProvider->getName('full', null, $entity);
        self::assertFalse($result);
    }

    public function testGetNameShortNotConfigured()
    {
        $entity = new \stdClass();

        $result = $this->entityNameProvider->getName('short', null, $entity);
        self::assertFalse($result);
    }

    public function testGetNameFull()
    {
        $entity = new TestEntity();
        $entity->setId(123);
        $entity->setName('name');
        $entity->setDescription('description');

        $result = $this->entityNameProvider->getName('full', null, $entity);
        self::assertEquals('name description', $result);
    }

    public function testGetNameShort()
    {
        $entity = new TestEntity();
        $entity->setId(123);
        $entity->setName('name');
        $entity->setDescription('description');

        $result = $this->entityNameProvider->getName('short', null, $entity);
        self::assertEquals('description', $result);
    }

    public function testGetNameFullNoNameNoIdentifier()
    {
        $entity = new TestEntity();

        $this->metadata->expects(self::once())
            ->method('getIdentifierFieldNames')
            ->willReturn([]);

        $result = $this->entityNameProvider->getName('full', null, $entity);
        self::assertFalse($result);
    }

    public function testGetNameShortNoNameNoIdentifier()
    {
        $entity = new TestEntity();

        $this->metadata->expects(self::once())
            ->method('getIdentifierFieldNames')
            ->willReturn([]);

        $result = $this->entityNameProvider->getName('short', null, $entity);
        self::assertFalse($result);
    }

    public function testGetNameFullNoName()
    {
        $entity = new TestEntity();
        $entity->setId(123);

        $this->metadata->expects(self::once())
            ->method('getIdentifierFieldNames')
            ->willReturn(['id']);

        $result = $this->entityNameProvider->getName('full', null, $entity);
        self::assertSame('123', $result);
    }

    public function testGetNameShortNoName()
    {
        $entity = new TestEntity();
        $entity->setId(123);

        $this->metadata->expects(self::once())
            ->method('getIdentifierFieldNames')
            ->willReturn(['id']);

        $result = $this->entityNameProvider->getName('short', null, $entity);
        self::assertSame('123', $result);
    }

    public function testGetNameFullNoNameAndNewEntity()
    {
        $entity = new TestEntity();

        $this->metadata->expects(self::once())
            ->method('getIdentifierFieldNames')
            ->willReturn(['id']);

        $result = $this->entityNameProvider->getName('full', null, $entity);
        self::assertNull($result);
    }

    public function testGetNameShortNoNameAndNewEntity()
    {
        $entity = new TestEntity();

        $this->metadata->expects(self::once())
            ->method('getIdentifierFieldNames')
            ->willReturn(['id']);

        $result = $this->entityNameProvider->getName('short', null, $entity);
        self::assertNull($result);
    }

    public function testGetNameDQLFullNotConfigured()
    {
        $result = $this->entityNameProvider->getNameDQL('full', null, \stdClass::class, 'alias');
        self::assertFalse($result);
    }

    public function testGetNameDQLShortNotConfigured()
    {
        $result = $this->entityNameProvider->getNameDQL('short', null, \stdClass::class, 'alias');
        self::assertFalse($result);
    }

    public function testGetNameDQLFull()
    {
        $this->metadata->expects(self::once())
            ->method('getIdentifierFieldNames')
            ->willReturn(['id']);

        $result = $this->entityNameProvider->getNameDQL('full', null, TestEntity::class, 'alias');
        self::assertEquals(
            'COALESCE(CAST(CONCAT_WS(\' \', alias.name, alias.description) AS string), CAST(alias.id AS string))',
            $result
        );
    }

    public function testGetNameDQLShort()
    {
        $this->metadata->expects(self::once())
            ->method('getIdentifierFieldNames')
            ->willReturn(['id']);

        $result = $this->entityNameProvider->getNameDQL('short', null, TestEntity::class, 'alias');
        self::assertEquals('COALESCE(CAST(alias.description AS string), CAST(alias.id AS string))', $result);
    }

    public function testGetNameDQLFullNoIdentity()
    {
        $this->metadata->expects(self::once())
            ->method('getIdentifierFieldNames')
            ->willReturn([]);

        $result = $this->entityNameProvider->getNameDQL('full', null, TestEntity::class, 'alias');
        self::assertEquals('CONCAT_WS(\' \', alias.name, alias.description)', $result);
    }

    public function testGetNameDQLShortNoIdentity()
    {
        $this->metadata->expects(self::once())
            ->method('getIdentifierFieldNames')
            ->willReturn([]);

        $result = $this->entityNameProvider->getNameDQL('short', null, TestEntity::class, 'alias');
        self::assertEquals('alias.description', $result);
    }
}
