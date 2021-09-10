<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\Provider;

use Doctrine\ORM\Mapping\ClassMetadata;
use Oro\Bundle\EntityConfigBundle\Config\Config;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Config\Id\EntityConfigId;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityExtendBundle\Provider\ExtendExclusionProvider;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class ExtendExclusionProviderTest extends \PHPUnit\Framework\TestCase
{
    private const ENTITY_CLASS = 'Test\Entity';
    private const FIELD_NAME   = 'testField';

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    private $configManager;

    protected function setUp(): void
    {
        $this->configManager = $this->createMock(ConfigManager::class);
    }

    public function testIsIgnoredEntityForNonConfigurableEntity()
    {
        $this->configManager->expects($this->once())
            ->method('hasConfig')
            ->with(self::ENTITY_CLASS)
            ->willReturn(false);

        $exclusionProvider = new ExtendExclusionProvider($this->configManager);

        $this->assertFalse(
            $exclusionProvider->isIgnoredEntity(self::ENTITY_CLASS)
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

        $exclusionProvider = new ExtendExclusionProvider($this->configManager);

        $this->assertTrue(
            $exclusionProvider->isIgnoredEntity(self::ENTITY_CLASS)
        );
    }

    public function testIsIgnoredEntityForHiddenEntity()
    {
        $this->configManager->expects($this->once())
            ->method('hasConfig')
            ->with(self::ENTITY_CLASS)
            ->willReturn(true);
        $this->configManager->expects($this->once())
            ->method('getEntityConfig')
            ->with('extend', self::ENTITY_CLASS)
            ->willReturn($this->getEntityConfig(self::ENTITY_CLASS));
        $this->configManager->expects($this->never())
            ->method('isHiddenModel');

        $exclusionProvider = new ExtendExclusionProvider($this->configManager);

        $this->assertFalse(
            $exclusionProvider->isIgnoredEntity(self::ENTITY_CLASS)
        );
    }

    public function testIsIgnoredEntityForHiddenEntityAndExcludeHiddenEntitiesRequested()
    {
        $this->configManager->expects($this->once())
            ->method('hasConfig')
            ->with(self::ENTITY_CLASS)
            ->willReturn(true);
        $this->configManager->expects($this->once())
            ->method('getEntityConfig')
            ->with('extend', self::ENTITY_CLASS)
            ->willReturn($this->getEntityConfig(self::ENTITY_CLASS));
        $this->configManager->expects($this->once())
            ->method('isHiddenModel')
            ->with(self::ENTITY_CLASS)
            ->willReturn(true);

        $exclusionProvider = new ExtendExclusionProvider($this->configManager, true);

        $this->assertTrue(
            $exclusionProvider->isIgnoredEntity(self::ENTITY_CLASS)
        );
    }

    public function testIsIgnoredEntityForRegularEntityAndExcludeHiddenEntitiesRequested()
    {
        $this->configManager->expects($this->once())
            ->method('hasConfig')
            ->with(self::ENTITY_CLASS)
            ->willReturn(true);
        $this->configManager->expects($this->once())
            ->method('getEntityConfig')
            ->with('extend', self::ENTITY_CLASS)
            ->willReturn($this->getEntityConfig(self::ENTITY_CLASS));
        $this->configManager->expects($this->once())
            ->method('isHiddenModel')
            ->with(self::ENTITY_CLASS)
            ->willReturn(false);

        $exclusionProvider = new ExtendExclusionProvider($this->configManager, true);

        $this->assertFalse(
            $exclusionProvider->isIgnoredEntity(self::ENTITY_CLASS)
        );
    }

    public function testIsIgnoredFieldForNonConfigurableField()
    {
        $metadata = new ClassMetadata(self::ENTITY_CLASS);

        $this->configManager->expects($this->once())
            ->method('hasConfig')
            ->with(self::ENTITY_CLASS, self::FIELD_NAME)
            ->willReturn(false);

        $exclusionProvider = new ExtendExclusionProvider($this->configManager);

        $this->assertFalse(
            $exclusionProvider->isIgnoredField($metadata, self::FIELD_NAME)
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

        $exclusionProvider = new ExtendExclusionProvider($this->configManager);

        $this->assertTrue(
            $exclusionProvider->isIgnoredField($metadata, self::FIELD_NAME)
        );
    }

    public function testIsIgnoredFieldForHiddenField()
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
        $this->configManager->expects($this->never())
            ->method('isHiddenModel');

        $exclusionProvider = new ExtendExclusionProvider($this->configManager);

        $this->assertFalse(
            $exclusionProvider->isIgnoredField($metadata, self::FIELD_NAME)
        );
    }

    public function testIsIgnoredFieldForHiddenFieldAndExcludeHiddenFieldsRequested()
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
        $this->configManager->expects($this->once())
            ->method('isHiddenModel')
            ->with(self::ENTITY_CLASS, self::FIELD_NAME)
            ->willReturn(true);

        $exclusionProvider = new ExtendExclusionProvider($this->configManager, false, true);

        $this->assertTrue(
            $exclusionProvider->isIgnoredField($metadata, self::FIELD_NAME)
        );
    }

    public function testIsIgnoredFieldForRegularFieldAndExcludeHiddenFieldsRequested()
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
        $this->configManager->expects($this->once())
            ->method('isHiddenModel')
            ->with(self::ENTITY_CLASS, self::FIELD_NAME)
            ->willReturn(false);

        $exclusionProvider = new ExtendExclusionProvider($this->configManager, false, true);

        $this->assertFalse(
            $exclusionProvider->isIgnoredField($metadata, self::FIELD_NAME)
        );
    }

    public function testIsIgnoredRelationForNonConfigurableField()
    {
        $metadata = new ClassMetadata(self::ENTITY_CLASS);

        $this->configManager->expects($this->once())
            ->method('hasConfig')
            ->with(self::ENTITY_CLASS, self::FIELD_NAME)
            ->willReturn(false);

        $exclusionProvider = new ExtendExclusionProvider($this->configManager);

        $this->assertFalse(
            $exclusionProvider->isIgnoredRelation($metadata, self::FIELD_NAME)
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

        $exclusionProvider = new ExtendExclusionProvider($this->configManager);

        $this->assertTrue(
            $exclusionProvider->isIgnoredRelation($metadata, self::FIELD_NAME)
        );
    }

    public function testIsIgnoredRelationForHiddenField()
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
        $this->configManager->expects($this->never())
            ->method('isHiddenModel');

        $exclusionProvider = new ExtendExclusionProvider($this->configManager);

        $this->assertFalse(
            $exclusionProvider->isIgnoredRelation($metadata, self::FIELD_NAME)
        );
    }

    public function testIsIgnoredRelationForHiddenFieldAndExcludeHiddenFieldsRequested()
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
        $this->configManager->expects($this->once())
            ->method('isHiddenModel')
            ->with(self::ENTITY_CLASS, self::FIELD_NAME)
            ->willReturn(true);

        $exclusionProvider = new ExtendExclusionProvider($this->configManager, false, true);

        $this->assertTrue(
            $exclusionProvider->isIgnoredRelation($metadata, self::FIELD_NAME)
        );
    }

    public function testIsIgnoredRelationForRegularFieldAndExcludeHiddenFieldsRequested()
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
        $this->configManager->expects($this->once())
            ->method('isHiddenModel')
            ->with(self::ENTITY_CLASS, self::FIELD_NAME)
            ->willReturn(false);

        $exclusionProvider = new ExtendExclusionProvider($this->configManager, false, true);

        $this->assertFalse(
            $exclusionProvider->isIgnoredRelation($metadata, self::FIELD_NAME)
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

        $exclusionProvider = new ExtendExclusionProvider($this->configManager);

        $this->assertTrue(
            $exclusionProvider->isIgnoredRelation($metadata, self::FIELD_NAME)
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

        $exclusionProvider = new ExtendExclusionProvider($this->configManager);

        $this->assertFalse(
            $exclusionProvider->isIgnoredRelation($metadata, self::FIELD_NAME)
        );
    }

    public function testIsIgnoredRelationWithTargetEntityAndExcludeHiddenEntitiesRequested()
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
        $this->configManager->expects($this->once())
            ->method('isHiddenModel')
            ->with('Test\TargetEntity')
            ->willReturn(false);

        $exclusionProvider = new ExtendExclusionProvider($this->configManager, true);

        $this->assertFalse(
            $exclusionProvider->isIgnoredRelation($metadata, self::FIELD_NAME)
        );
    }

    public function testIsIgnoredRelationWithHiddenTargetEntityAndExcludeHiddenEntitiesRequested()
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
        $this->configManager->expects($this->once())
            ->method('isHiddenModel')
            ->with('Test\TargetEntity')
            ->willReturn(true);

        $exclusionProvider = new ExtendExclusionProvider($this->configManager, true);

        $this->assertTrue(
            $exclusionProvider->isIgnoredRelation($metadata, self::FIELD_NAME)
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
