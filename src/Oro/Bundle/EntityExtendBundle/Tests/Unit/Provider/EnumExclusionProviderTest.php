<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\Provider;

use Doctrine\ORM\Mapping\ClassMetadata;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityExtendBundle\Provider\EnumExclusionProvider;

class EnumExclusionProviderTest extends \PHPUnit\Framework\TestCase
{
    const ENTITY_CLASS = 'Test\Entity';

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $configManager;

    /** @var EnumExclusionProvider */
    protected $exclusionProvider;

    protected function setUp()
    {
        $this->configManager = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Config\ConfigManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->exclusionProvider = new EnumExclusionProvider($this->configManager);
    }

    public function testIsIgnoredEntity()
    {
        $this->assertFalse(
            $this->exclusionProvider->isIgnoredEntity(self::ENTITY_CLASS)
        );
    }

    public function testIsIgnoredFieldWithoutMultiEnumSnapshotSuffix()
    {
        $metadata = new ClassMetadata(self::ENTITY_CLASS);

        $this->assertFalse(
            $this->exclusionProvider->isIgnoredField($metadata, 'test')
        );
    }

    public function testIsIgnoredFieldForSnapshotFieldName()
    {
        $metadata = new ClassMetadata(self::ENTITY_CLASS);

        $this->assertFalse(
            $this->exclusionProvider->isIgnoredField($metadata, 'Snapshot')
        );
    }

    public function testIsIgnoredFieldForNonConfigurableGuessedField()
    {
        $metadata = new ClassMetadata(self::ENTITY_CLASS);

        $this->configManager->expects($this->once())
            ->method('hasConfig')
            ->with(self::ENTITY_CLASS, 'test')
            ->willReturn(false);

        $this->assertFalse(
            $this->exclusionProvider->isIgnoredField($metadata, 'testSnapshot')
        );
    }

    public function testIsIgnoredFieldForNotMultiEnumSnapshot()
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
            $this->exclusionProvider->isIgnoredField($metadata, 'testSnapshot')
        );
    }

    public function testIsIgnoredFieldForMultiEnumSnapshot()
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
            $this->exclusionProvider->isIgnoredField($metadata, 'testSnapshot')
        );
    }

    public function testIsIgnoredRelation()
    {
        $metadata = new ClassMetadata(self::ENTITY_CLASS);

        $this->assertFalse(
            $this->exclusionProvider->isIgnoredRelation($metadata, 'testRelation')
        );
    }
}
