<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\Provider;

use Doctrine\ORM\Mapping\ClassMetadata;
use Oro\Bundle\EntityConfigBundle\Config\Config;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Config\Id\EntityConfigId;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityExtendBundle\Provider\ExtendExclusionProvider;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendConfigDumper;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class ExtendExclusionProviderTest extends \PHPUnit\Framework\TestCase
{
    private const ENTITY_CLASS = 'Test\Entity';
    private const FIELD_NAME = 'testField';

    /** @var ConfigManager|\PHPUnit\Framework\MockObject\MockObject */
    private $configManager;

    /** @var ExtendExclusionProvider */
    private $exclusionProvider;

    protected function setUp(): void
    {
        $this->configManager = $this->createMock(ConfigManager::class);

        $this->exclusionProvider = new ExtendExclusionProvider($this->configManager);
    }

    public function testIsIgnoredEntityForNonConfigurableEntity()
    {
        $this->configManager->expects($this->once())
            ->method('hasConfig')
            ->with(self::ENTITY_CLASS)
            ->willReturn(false);

        $this->assertFalse(
            $this->exclusionProvider->isIgnoredEntity(self::ENTITY_CLASS)
        );
    }

    public function testIsIgnoredEntityForNotAccessibleEntity()
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

    public function testIsIgnoredEntityForAccessibleEntity()
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

    public function testIsIgnoredFieldForNonConfigurableField()
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

    public function testIsIgnoredFieldForNotAccessibleField()
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

    public function testIsIgnoredFieldForAccessibleField()
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

    public function testIsIgnoredRelationForNonConfigurableField()
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

    public function testIsIgnoredRelationForNotAccessibleField()
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

    public function testIsIgnoredRelationForAccessibleField()
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

    public function testIsIgnoredRelationForNotAccessibleTargetEntity()
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

    public function testIsIgnoredRelationWithTargetEntity()
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

    public function testIsIgnoredRelationForDefaultFieldOfToManyRelation()
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

    public function testIsIgnoredRelationForDefaultFieldOfToManyRelationForNotAccessibleRelation()
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
