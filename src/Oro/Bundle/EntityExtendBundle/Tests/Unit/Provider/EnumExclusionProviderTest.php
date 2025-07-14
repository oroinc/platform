<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\Provider;

use Doctrine\ORM\Mapping\ClassMetadata;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityExtendBundle\Provider\EnumExclusionProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class EnumExclusionProviderTest extends TestCase
{
    private const ENTITY_CLASS = 'Test\Entity';

    private ConfigManager&MockObject $configManager;
    private EnumExclusionProvider $exclusionProvider;

    #[\Override]
    protected function setUp(): void
    {
        $this->configManager = $this->createMock(ConfigManager::class);

        $this->exclusionProvider = new EnumExclusionProvider($this->configManager);
    }

    public function testIsIgnoredEntity(): void
    {
        $this->assertFalse(
            $this->exclusionProvider->isIgnoredEntity(self::ENTITY_CLASS)
        );
    }

    public function testIsIgnoredFieldWithoutMultiEnumSnapshotSuffix(): void
    {
        $metadata = new ClassMetadata(self::ENTITY_CLASS);

        $this->assertFalse(
            $this->exclusionProvider->isIgnoredField($metadata, 'test')
        );
    }

    public function testIsIgnoredFieldForSnapshotFieldName(): void
    {
        $metadata = new ClassMetadata(self::ENTITY_CLASS);

        $this->assertFalse(
            $this->exclusionProvider->isIgnoredField($metadata, 'Snapshot')
        );
    }

    public function testIsIgnoredFieldForNonConfigurableGuessedField(): void
    {
        $metadata = new ClassMetadata(self::ENTITY_CLASS);

        $this->configManager->expects($this->once())
            ->method('hasConfig')
            ->with(self::ENTITY_CLASS, 'test')
            ->willReturn(false);

        $this->assertFalse(
            $this->exclusionProvider->isIgnoredField($metadata, 'test')
        );
    }

    public function testIsIgnoredFieldForNotMultiEnumSnapshot(): void
    {
        $metadata = new ClassMetadata(self::ENTITY_CLASS);

        $this->configManager->expects($this->once())
            ->method('hasConfig')
            ->with(self::ENTITY_CLASS, 'test')
            ->willReturn(true);
        $this->configManager->expects($this->once())
            ->method('getId')
            ->with('extend', self::ENTITY_CLASS, 'test')
            ->willReturn(new FieldConfigId('extend', self::ENTITY_CLASS, 'test', 'string'));

        $this->assertFalse(
            $this->exclusionProvider->isIgnoredField($metadata, 'test')
        );
    }

    public function testIsIgnoredFieldForMultiEnumSnapshot(): void
    {
        $metadata = new ClassMetadata(self::ENTITY_CLASS);

        $this->configManager->expects($this->once())
            ->method('hasConfig')
            ->with(self::ENTITY_CLASS, 'test')
            ->willReturn(true);
        $this->configManager->expects($this->once())
            ->method('getId')
            ->with('extend', self::ENTITY_CLASS, 'test')
            ->willReturn(new FieldConfigId('extend', self::ENTITY_CLASS, 'test', 'multiEnum'));

        $this->assertTrue(
            $this->exclusionProvider->isIgnoredField($metadata, 'test')
        );
    }

    public function testIsIgnoredRelation(): void
    {
        $metadata = new ClassMetadata(self::ENTITY_CLASS);

        $this->assertFalse(
            $this->exclusionProvider->isIgnoredRelation($metadata, 'testRelation')
        );
    }
}
