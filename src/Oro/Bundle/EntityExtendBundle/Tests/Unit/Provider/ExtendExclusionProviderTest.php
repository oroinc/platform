<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\Provider;

use Doctrine\ORM\Mapping\ClassMetadata;
use Oro\Bundle\EntityConfigBundle\Config\Config;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Config\Id\EntityConfigId;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityExtendBundle\Provider\ExtendExclusionProvider;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendConfigDumper;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class ExtendExclusionProviderTest extends TestCase
{
    private const ENTITY_CLASS = 'Test\Entity';
    private const FIELD_NAME = 'testField';

    private ConfigManager&MockObject $configManager;
    private ExtendExclusionProvider $exclusionProvider;

    #[\Override]
    protected function setUp(): void
    {
        $this->configManager = $this->createMock(ConfigManager::class);

        $this->exclusionProvider = new ExtendExclusionProvider($this->configManager);
    }

    public function testIsIgnoredEntityForNonConfigurableEntity(): void
    {
        $this->configManager->expects($this->once())
            ->method('hasConfig')
            ->with(self::ENTITY_CLASS)
            ->willReturn(false);

        $this->assertFalse(
            $this->exclusionProvider->isIgnoredEntity(self::ENTITY_CLASS)
        );
    }

    public function testIsIgnoredEntityForNotAccessibleEntity(): void
    {
        $this->configManager->expects($this->once())
            ->method('hasConfig')
            ->with(self::ENTITY_CLASS)
            ->willReturn(true);
        $this->configManager->expects($this->once())
            ->method('getEntityConfig')
            ->with('extend', self::ENTITY_CLASS)
            ->willReturn($this->getEntityConfig(self::ENTITY_CLASS, ['is_extend' => true, 'is_deleted' => true]));

        $this->assertTrue(
            $this->exclusionProvider->isIgnoredEntity(self::ENTITY_CLASS)
        );
    }

    public function testIsIgnoredEntityForAccessibleEntity(): void
    {
        $this->configManager->expects($this->once())
            ->method('hasConfig')
            ->with(self::ENTITY_CLASS)
            ->willReturn(true);
        $this->configManager->expects($this->once())
            ->method('getEntityConfig')
            ->with('extend', self::ENTITY_CLASS)
            ->willReturn($this->getEntityConfig(self::ENTITY_CLASS));

        $this->assertFalse(
            $this->exclusionProvider->isIgnoredEntity(self::ENTITY_CLASS)
        );
    }

    public function testIsIgnoredFieldForNonConfigurableField(): void
    {
        $metadata = new ClassMetadata(self::ENTITY_CLASS);

        $this->configManager->expects($this->once())
            ->method('hasConfig')
            ->with(self::ENTITY_CLASS, self::FIELD_NAME)
            ->willReturn(false);

        $this->assertFalse(
            $this->exclusionProvider->isIgnoredField($metadata, self::FIELD_NAME)
        );
    }

    public function testIsIgnoredFieldForNotAccessibleField(): void
    {
        $metadata = new ClassMetadata(self::ENTITY_CLASS);

        $this->configManager->expects($this->once())
            ->method('hasConfig')
            ->with(self::ENTITY_CLASS)
            ->willReturn(true);
        $this->configManager->expects($this->once())
            ->method('getFieldConfig')
            ->with('extend', self::ENTITY_CLASS, self::FIELD_NAME)
            ->willReturn(
                $this->getFieldConfig(
                    self::ENTITY_CLASS,
                    self::FIELD_NAME,
                    ['is_extend' => true, 'is_deleted' => true]
                )
            );

        $this->assertTrue(
            $this->exclusionProvider->isIgnoredField($metadata, self::FIELD_NAME)
        );
    }

    public function testIsIgnoredFieldForAccessibleField(): void
    {
        $metadata = new ClassMetadata(self::ENTITY_CLASS);

        $this->configManager->expects($this->once())
            ->method('hasConfig')
            ->with(self::ENTITY_CLASS)
            ->willReturn(true);
        $this->configManager->expects($this->once())
            ->method('getFieldConfig')
            ->with('extend', self::ENTITY_CLASS, self::FIELD_NAME)
            ->willReturn($this->getFieldConfig(self::ENTITY_CLASS, self::FIELD_NAME));

        $this->assertFalse(
            $this->exclusionProvider->isIgnoredField($metadata, self::FIELD_NAME)
        );
    }

    public function testIsIgnoredRelationForNonConfigurableField(): void
    {
        $metadata = new ClassMetadata(self::ENTITY_CLASS);

        $this->configManager->expects($this->once())
            ->method('hasConfig')
            ->with(self::ENTITY_CLASS, self::FIELD_NAME)
            ->willReturn(false);

        $this->assertFalse(
            $this->exclusionProvider->isIgnoredRelation($metadata, self::FIELD_NAME)
        );
    }

    public function testIsIgnoredRelationForNotAccessibleField(): void
    {
        $metadata = new ClassMetadata(self::ENTITY_CLASS);

        $this->configManager->expects($this->once())
            ->method('hasConfig')
            ->with(self::ENTITY_CLASS)
            ->willReturn(true);
        $this->configManager->expects($this->once())
            ->method('getFieldConfig')
            ->with('extend', self::ENTITY_CLASS, self::FIELD_NAME)
            ->willReturn(
                $this->getFieldConfig(
                    self::ENTITY_CLASS,
                    self::FIELD_NAME,
                    ['is_extend' => true, 'is_deleted' => true]
                )
            );

        $this->assertTrue(
            $this->exclusionProvider->isIgnoredRelation($metadata, self::FIELD_NAME)
        );
    }

    public function testIsIgnoredRelationForAccessibleField(): void
    {
        $metadata = new ClassMetadata(self::ENTITY_CLASS);

        $this->configManager->expects($this->once())
            ->method('hasConfig')
            ->with(self::ENTITY_CLASS)
            ->willReturn(true);
        $this->configManager->expects($this->once())
            ->method('getFieldConfig')
            ->with('extend', self::ENTITY_CLASS, self::FIELD_NAME)
            ->willReturn($this->getFieldConfig(self::ENTITY_CLASS, self::FIELD_NAME));

        $this->assertFalse(
            $this->exclusionProvider->isIgnoredRelation($metadata, self::FIELD_NAME)
        );
    }

    public function testIsIgnoredRelationForNotAccessibleTargetEntity(): void
    {
        $metadata = new ClassMetadata(self::ENTITY_CLASS);

        $this->configManager->expects($this->once())
            ->method('hasConfig')
            ->with(self::ENTITY_CLASS)
            ->willReturn(true);
        $this->configManager->expects($this->once())
            ->method('getFieldConfig')
            ->with('extend', self::ENTITY_CLASS, self::FIELD_NAME)
            ->willReturn(
                $this->getFieldConfig(
                    self::ENTITY_CLASS,
                    self::FIELD_NAME,
                    ['target_entity' => 'Test\TargetEntity']
                )
            );
        $this->configManager->expects($this->once())
            ->method('getEntityConfig')
            ->with('extend', 'Test\TargetEntity')
            ->willReturn(
                $this->getEntityConfig('Test\TargetEntity', ['is_extend' => true, 'is_deleted' => true])
            );

        $this->assertTrue(
            $this->exclusionProvider->isIgnoredRelation($metadata, self::FIELD_NAME)
        );
    }

    public function testIsIgnoredRelationWithTargetEntity(): void
    {
        $metadata = new ClassMetadata(self::ENTITY_CLASS);

        $this->configManager->expects($this->once())
            ->method('hasConfig')
            ->with(self::ENTITY_CLASS)
            ->willReturn(true);
        $this->configManager->expects($this->once())
            ->method('getFieldConfig')
            ->with('extend', self::ENTITY_CLASS, self::FIELD_NAME)
            ->willReturn(
                $this->getFieldConfig(
                    self::ENTITY_CLASS,
                    self::FIELD_NAME,
                    ['target_entity' => 'Test\TargetEntity']
                )
            );
        $this->configManager->expects($this->once())
            ->method('getEntityConfig')
            ->with('extend', 'Test\TargetEntity')
            ->willReturn(
                $this->getEntityConfig('Test\TargetEntity')
            );

        $this->assertFalse(
            $this->exclusionProvider->isIgnoredRelation($metadata, self::FIELD_NAME)
        );
    }

    public function testIsIgnoredRelationForDefaultFieldOfToManyRelation(): void
    {
        $metadata = new ClassMetadata(self::ENTITY_CLASS);
        $fieldName = ExtendConfigDumper::DEFAULT_PREFIX . self::FIELD_NAME;

        $this->configManager->expects($this->exactly(2))
            ->method('hasConfig')
            ->withConsecutive([self::ENTITY_CLASS, $fieldName], [self::ENTITY_CLASS, self::FIELD_NAME])
            ->willReturnOnConsecutiveCalls(false, true);

        $this->configManager->expects($this->once())
            ->method('getFieldConfig')
            ->with('extend', self::ENTITY_CLASS, self::FIELD_NAME)
            ->willReturn(
                $this->getFieldConfig(
                    self::ENTITY_CLASS,
                    self::FIELD_NAME,
                    ['target_entity' => 'Test\TargetEntity']
                )
            );
        $this->configManager->expects($this->once())
            ->method('getEntityConfig')
            ->with('extend', 'Test\TargetEntity')
            ->willReturn(
                $this->getEntityConfig('Test\TargetEntity')
            );

        $this->assertFalse(
            $this->exclusionProvider->isIgnoredRelation($metadata, $fieldName)
        );
    }

    public function testIsIgnoredRelationForDefaultFieldOfToManyRelationForNotAccessibleRelation(): void
    {
        $metadata = new ClassMetadata(self::ENTITY_CLASS);
        $fieldName = ExtendConfigDumper::DEFAULT_PREFIX . self::FIELD_NAME;

        $this->configManager->expects($this->exactly(2))
            ->method('hasConfig')
            ->withConsecutive([self::ENTITY_CLASS, $fieldName], [self::ENTITY_CLASS, self::FIELD_NAME])
            ->willReturnOnConsecutiveCalls(false, true);

        $this->configManager->expects($this->once())
            ->method('getFieldConfig')
            ->with('extend', self::ENTITY_CLASS, self::FIELD_NAME)
            ->willReturn(
                $this->getFieldConfig(
                    self::ENTITY_CLASS,
                    self::FIELD_NAME,
                    ['is_extend' => true, 'is_deleted' => true, 'target_entity' => 'Test\TargetEntity']
                )
            );
        $this->configManager->expects($this->never())
            ->method('getEntityConfig');

        $this->assertTrue(
            $this->exclusionProvider->isIgnoredRelation($metadata, $fieldName)
        );
    }

    private function getEntityConfig(string $className, array $values = []): Config
    {
        $config = new Config(new EntityConfigId('extend', $className));
        $config->setValues($values);

        return $config;
    }

    private function getFieldConfig(string $className, string $fieldName, array $values = []): Config
    {
        $config = new Config(new FieldConfigId('extend', $className, $fieldName));
        $config->setValues($values);

        return $config;
    }
}
