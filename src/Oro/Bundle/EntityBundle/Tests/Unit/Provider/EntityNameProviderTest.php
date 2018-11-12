<?php

namespace Oro\Bundle\EntityBundle\Tests\Unit\Provider;

use Oro\Bundle\EntityBundle\Provider\EntityNameProvider;
use Oro\Bundle\EntityBundle\Tests\Unit\ORM\Fixtures\TestEntity;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Tests\Unit\ConfigProviderMock;

class EntityNameProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $doctrine;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $metadata;

    /** @var EntityNameProvider */
    protected $entityNameProvider;

    /** @var ConfigProviderMock */
    protected $extendConfigProvider;

    protected function setUp()
    {
        $this->doctrine = $this->createMock('Doctrine\Common\Persistence\ManagerRegistry');
        $manager        = $this->getMockBuilder('Doctrine\Common\Persistence\ObjectManager')
            ->disableOriginalConstructor()
            ->getMock();
        $this->metadata = $this->getMockBuilder('Doctrine\Common\Persistence\Mapping\ClassMetadata')
            ->disableOriginalConstructor()
            ->getMock();

        $this->doctrine->expects($this->any())
            ->method('getManagerForClass')
            ->willReturnMap(
                [
                    [TestEntity::class, $manager]
                ]
            );
        $manager->expects($this->any())
            ->method('getClassMetadata')
            ->willReturnMap(
                [
                    [TestEntity::class, $this->metadata]
                ]
            );

        $configManager = $this->getMockBuilder(ConfigManager::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->extendConfigProvider = new ConfigProviderMock($configManager, 'extend');
        $this->entityNameProvider = new EntityNameProvider($this->doctrine, $this->extendConfigProvider);
    }

    public function testGetNameForUnsupportedFormat()
    {
        $result = $this->entityNameProvider->getName('test', null, new TestEntity());
        $this->assertFalse($result);
    }

    public function testGetName()
    {
        $entity = new TestEntity();
        $entity->setName('test');

        $this->metadata->expects($this->any())
            ->method('hasField')
            ->willReturnMap(
                [
                    ['name', true]
                ]
            );
        $this->metadata->expects($this->once())
            ->method('getTypeOfField')
            ->with('name')
            ->willReturn('string');

        $result = $this->entityNameProvider->getName('short', null, $entity);
        $this->assertEquals('test', $result);
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
                    'is_extend' => true,
                    'is_deleted' => false
                ]
            ]
        );

        $this->assertEquals(
            'test',
            $this->entityNameProvider->getName('short', null, $entity)
        );

        $this->assertEquals(
            'test description',
            $this->entityNameProvider->getName('full', null, $entity)
        );

        $this->initEntityFieldsMetadata(
            false,
            [
                'name' => [
                    'is_extend' => true,
                    'is_deleted' => true
                ]
            ]
        );

        $this->assertFalse(
            $this->entityNameProvider->getName('short', null, $entity)
        );

        $this->assertEquals(
            'description',
            $this->entityNameProvider->getName('full', null, $entity)
        );
    }

    public function testGetNameForNotManageableEntity()
    {
        $entity = new \stdClass();

        $result = $this->entityNameProvider->getName('short', null, $entity);
        $this->assertFalse($result);
    }

    public function testGetNameNoAppropriateField()
    {
        $entity = new TestEntity();

        $result = $this->entityNameProvider->getName('short', null, $entity);
        $this->assertFalse($result);
    }

    public function testGetNameWhenEmptyNameButHasIdentifier()
    {
        $entity = new TestEntity(123);

        $this->initEntityFieldsMetadata(true);

        $result = $this->entityNameProvider->getName('short', null, $entity);
        $this->assertEquals(123, $result);

        $result = $this->entityNameProvider->getName('full', null, $entity);
        $this->assertEquals(123, $result);
    }

    public function testGetNameFullEmptyNameButNoIdentifier()
    {
        $entity = new TestEntity(123);
        $this->initEntityFieldsMetadata(false);

        $result = $this->entityNameProvider->getName('full', null, $entity);
        $this->assertFalse($result);
    }

    public function testGetNameDQLForUnsupportedFormat()
    {
        $result = $this->entityNameProvider->getNameDQL('test', null, TestEntity::class, 'alias');
        $this->assertFalse($result);
    }

    public function testGetNameDQLShortNoIdentifier()
    {
        $this->metadata->expects($this->any())
            ->method('hasField')
            ->willReturnMap(
                [
                    ['name', true]
                ]
            );
        $this->metadata->expects($this->once())
            ->method('getTypeOfField')
            ->with('name')
            ->willReturn('string');
        $this->metadata->expects($this->once())
            ->method('getIdentifierFieldNames')
            ->willReturn([]);

        $result = $this->entityNameProvider->getNameDQL('short', null, TestEntity::class, 'alias');
        $this->assertEquals('alias.name', $result);
    }

    public function testGetNameDQLShortForExtendedEntity()
    {
        $this->initEntityFieldsMetadata(
            false,
            [
                'name' => [
                    'is_extend' => true,
                    'is_deleted' => false
                ]
            ]
        );

        $shortFormatDQL = $this->entityNameProvider->getNameDQL('short', null, TestEntity::class, 'alias');
        $this->assertEquals('alias.name', $shortFormatDQL);

        $this->initEntityFieldsMetadata(
            false,
            [
                'name' => [
                    'is_extend' => true,
                    'is_deleted' => true
                ]
            ]
        );

        $shortFormatDQL = $this->entityNameProvider->getNameDQL('short', null, TestEntity::class, 'alias');
        $this->assertFalse($shortFormatDQL);
    }

    public function testGetNameDQLShortWithIdentifier()
    {
        $this->initEntityFieldsMetadata(true);

        $result = $this->entityNameProvider->getNameDQL('short', null, TestEntity::class, 'alias');
        $this->assertEquals('COALESCE(CAST(alias.name AS string), CAST(alias.id AS string))', $result);
    }

    public function testGetNameDQLForNotManageableEntity()
    {
        $result = $this->entityNameProvider->getNameDQL('short', null, 'Test\Class', 'alias');
        $this->assertFalse($result);
    }

    public function testGetNameDQLNoAppropriateField()
    {
        $result = $this->entityNameProvider->getNameDQL('short', null, TestEntity::class, 'alias');
        $this->assertFalse($result);
    }

    public function testGetNameDQLShortNoAppropriateField()
    {
        $result = $this->entityNameProvider->getNameDQL('short', null, TestEntity::class, 'alias');

        $this->assertFalse($result);
    }

    public function testGetNameDQLFullNoAppropriateFields()
    {
        $result = $this->entityNameProvider->getNameDQL('full', null, TestEntity::class, 'alias');
        $this->assertFalse($result);
    }

    public function testGetNameDQLFull()
    {
        $this->initEntityFieldsMetadata(true);

        $result = $this->entityNameProvider->getNameDQL('full', null, TestEntity::class, 'alias');
        $this->assertEquals(
            'COALESCE(CAST(CONCAT_WS(\' \', alias.name, alias.description) AS string), CAST(alias.id AS string))',
            $result
        );
    }

    public function testGetNameDQLFullNoIdentifier()
    {
        $this->initEntityFieldsMetadata();

        $result = $this->entityNameProvider->getNameDQL('full', null, TestEntity::class, 'alias');
        $this->assertEquals('CONCAT_WS(\' \', alias.name, alias.description)', $result);
    }

    public function testGetNameDQLFullForExtendedEntity()
    {
        $this->initEntityFieldsMetadata(
            false,
            [
                'name' => [
                    'is_extend' => true,
                    'is_deleted' => false
                ]
            ]
        );

        $result = $this->entityNameProvider->getNameDQL('full', null, TestEntity::class, 'alias');
        $this->assertEquals('CONCAT_WS(\' \', alias.name, alias.description)', $result);

        $this->initEntityFieldsMetadata(
            false,
            [
                'name' => [
                    'is_extend' => true,
                    'is_deleted' => true
                ]
            ]
        );

        $result = $this->entityNameProvider->getNameDQL('full', null, TestEntity::class, 'alias');
        $this->assertEquals('alias.description', $result);
    }

    protected function initEntityFieldsMetadata($initIdentityField = false, array $extendedFieldConfig = [])
    {
        $this->metadata->expects($this->any())
            ->method('getIdentifierFieldNames')
            ->willReturn($initIdentityField ? ['id'] : []);

        $this->metadata->expects($this->any())
            ->method('hasField')
            ->willReturnMap(
                [
                    ['name', true],
                    ['description', true]
                ]
            );

        $this->metadata->expects($this->any())
            ->method('getName')
            ->willReturn(TestEntity::class);

        $this->metadata->expects($this->any())
            ->method('getTypeOfField')
            ->willReturnMap(
                [
                    ['name', 'string'],
                    ['description', 'string']
                ]
            );

        $this->metadata->expects($this->any())
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
