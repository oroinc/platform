<?php

namespace Oro\Bundle\AttachmentBundle\Tests\Unit\Tools;

use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\AttachmentBundle\Entity\FileItem;
use Oro\Bundle\AttachmentBundle\Tests\Unit\Stub\Entity\TestEntity1;
use Oro\Bundle\AttachmentBundle\Tests\Unit\Stub\Entity\TestEntity2;
use Oro\Bundle\AttachmentBundle\Tests\Unit\Stub\Entity\TestEntity3;
use Oro\Bundle\AttachmentBundle\Tools\FileEntityConfigDumperExtension;
use Oro\Bundle\EntityConfigBundle\Config\Config;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Config\Id\EntityConfigId;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\EntityExtendBundle\Extend\FieldTypeHelper;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;

class FileEntityConfigDumperExtensionTest extends \PHPUnit\Framework\TestCase
{
    /** @var ConfigManager|\PHPUnit\Framework\MockObject\MockObject */
    private $configManager;

    /** @var FieldTypeHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $fieldTypeHelper;

    /** @var FileEntityConfigDumperExtension */
    private $extension;

    protected function setUp(): void
    {
        $this->configManager = $this->createMock(ConfigManager::class);
        $this->fieldTypeHelper = $this->createMock(FieldTypeHelper::class);

        $this->extension = new FileEntityConfigDumperExtension(
            $this->configManager,
            $this->fieldTypeHelper
        );
    }

    public function testPreUpdateWithoutConfigs()
    {
        $this->configManager->expects(self::once())
            ->method('getConfigs')
            ->with('extend')
            ->willReturn([]);

        $this->configManager->expects(self::never())
            ->method('persist');

        $this->extension->preUpdate();
    }

    public function testPreUpdateWithoutIsExtendConfigs()
    {
        $this->configManager->expects(self::once())
            ->method('getConfigs')
            ->with('extend')
            ->willReturn([
                new Config(new EntityConfigId('extend')),
            ]);

        $this->configManager->expects(self::never())
            ->method('persist');

        $this->extension->preUpdate();
    }

    public function testPreUpdateWithUnsupportedState()
    {
        $entityConfig = new Config(new EntityConfigId('extend', TestEntity1::class));
        $entityConfig->set('is_extend', true);

        $fieldConfig = new Config(new FieldConfigId('extend', TestEntity1::class, 'testField'));
        $fieldConfig->set('state', 'unsupported state');

        $this->configManager->expects(self::exactly(2))
            ->method('getConfigs')
            ->willReturnMap([
                ['extend', null, false, [$entityConfig]],
                ['extend', TestEntity1::class, false, [$fieldConfig]]
            ]);
        $this->configManager->expects(self::never())
            ->method('persist');

        $this->extension->preUpdate();
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testPreUpdateWithManyToOneRelation()
    {
        $entityConfig = $this->createExtendEntityConfig(TestEntity1::class);
        $fieldConfig = $this->createExtendFieldConfig(TestEntity1::class, 'fieldName', 'type');

        $imageEntityConfig = $this->createExtendEntityConfig(TestEntity2::class);
        $imageFieldConfig = $this->createExtendFieldConfig(TestEntity2::class, 'imageFieldName', 'image');
        $imageImportFieldConfig = $this->createImportExportFieldConfig(TestEntity2::class, 'imageFieldName');

        $imageFieldConfig->set('cascade', ['persist', 'refresh']);

        $fileEntityConfig = $this->createExtendEntityConfig(TestEntity3::class);
        $fileFieldConfig = $this->createExtendFieldConfig(TestEntity3::class, 'fileFieldName', 'file');
        $fileImportFieldConfig = $this->createImportExportFieldConfig(TestEntity3::class, 'fileFieldName');

        $imageFieldConfig->set('cascade', ['refresh']);

        $this->configManager->expects(self::exactly(4))
            ->method('getConfigs')
            ->willReturnMap([
                ['extend', null, false, [$entityConfig, $imageEntityConfig, $fileEntityConfig]],
                ['extend', TestEntity1::class, false, [$fieldConfig]],
                ['extend', TestEntity2::class, false, [$imageFieldConfig]],
                ['extend', TestEntity3::class, false, [$fileFieldConfig]],
            ]);

        $this->configManager->expects(self::exactly(2))
            ->method('getFieldConfig')
            ->willReturnMap([
                ['importexport', TestEntity2::class, 'imageFieldName', $imageImportFieldConfig],
                ['importexport', TestEntity3::class, 'fileFieldName', $fileImportFieldConfig],
            ]);

        $this->configManager->expects(self::exactly(4))
            ->method('getEntityConfig')
            ->willReturnMap([
                ['extend', TestEntity2::class, $imageEntityConfig],
                ['extend', TestEntity3::class, $fileEntityConfig],
            ]);

        $this->fieldTypeHelper->expects(self::exactly(4))
            ->method('getUnderlyingType')
            ->willReturnMap([
                ['image', 'manyToOne'],
                ['file', 'manyToOne'],
            ]);

        $this->configManager->expects(self::exactly(6))
            ->method('persist')
            ->withConsecutive(
                [$imageImportFieldConfig],
                [$imageFieldConfig],
                [$imageEntityConfig],
                [$fileImportFieldConfig],
                [$fileFieldConfig],
                [$fileEntityConfig]
            );

        $this->extension->preUpdate();

        $this->assertEquals(['is_extend' => true], $entityConfig->all());
        $this->assertEquals(['state' => ExtendScope::STATE_NEW], $fieldConfig->all());

        $imageRelationKey = ExtendHelper::buildRelationKey(
            TestEntity2::class,
            'imageFieldName',
            'manyToOne',
            File::class
        );
        $this->assertEquals(
            [
                'is_extend' => true,
                'relation' => [
                    $imageRelationKey => [
                        'field_id' => new FieldConfigId('extend', TestEntity2::class, 'imageFieldName', 'manyToOne'),
                        'owner' => true,
                        'target_entity' => File::class,
                        'target_field_id' => false,
                        'cascade' => [
                            'refresh',
                            'persist',
                        ],
                        'on_delete' => 'SET NULL',
                    ],
                ],
            ],
            $imageEntityConfig->all()
        );

        $this->assertEquals(
            [
                'state' => ExtendScope::STATE_NEW,
                'target_entity' => File::class,
                'target_field' => 'id',
                'cascade' => [
                    'refresh',
                    'persist',
                ],
                'on_delete' => 'SET NULL',
                'relation_key' => $imageRelationKey,
            ],
            $imageFieldConfig->all()
        );

        $fileRelationKey = ExtendHelper::buildRelationKey(
            TestEntity3::class,
            'fileFieldName',
            'manyToOne',
            File::class
        );
        $this->assertEquals(
            [
                'is_extend' => true,
                'relation' => [
                    $fileRelationKey => [
                        'field_id' => new FieldConfigId('extend', TestEntity3::class, 'fileFieldName', 'manyToOne'),
                        'owner' => true,
                        'target_entity' => File::class,
                        'target_field_id' => false,
                        'cascade' => [
                            'persist',
                        ],
                        'on_delete' => 'SET NULL',
                    ],
                ],
            ],
            $fileEntityConfig->all()
        );

        $this->assertEquals(
            [
                'state' => ExtendScope::STATE_NEW,
                'target_entity' => File::class,
                'target_field' => 'id',
                'cascade' => [
                    'persist',
                ],
                'on_delete' => 'SET NULL',
                'relation_key' => $fileRelationKey,
            ],
            $fileFieldConfig->all()
        );
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testPreUpdateWithOneToManyRelation()
    {
        $entityConfig = $this->createExtendEntityConfig(TestEntity1::class);
        $fieldConfig = $this->createExtendFieldConfig(TestEntity1::class, 'fieldName', 'type');

        $imagesEntityConfig = $this->createExtendEntityConfig(TestEntity2::class);
        $imagesFieldConfig = $this->createExtendFieldConfig(TestEntity2::class, 'imagesFieldName', 'multiImage');

        $imagesFieldConfig->set('cascade', ['persist', 'remove', 'refresh']);

        $filesEntityConfig = $this->createExtendEntityConfig(TestEntity3::class);
        $filesFieldConfig = $this->createExtendFieldConfig(TestEntity3::class, 'filesFieldName', 'multiFile');

        $filesFieldConfig->set('cascade', ['persist', 'remove', 'refresh']);

        $fileItemEntityConfig = $this->createExtendEntityConfig(FileItem::class);

        $this->configManager->expects(self::exactly(4))
            ->method('getConfigs')
            ->willReturnMap([
                ['extend', null, false, [$entityConfig, $imagesEntityConfig, $filesEntityConfig]],
                ['extend', TestEntity1::class, false, [$fieldConfig]],
                ['extend', TestEntity2::class, false, [$imagesFieldConfig]],
                ['extend', TestEntity3::class, false, [$filesFieldConfig]],
            ]);

        $this->configManager->expects(self::exactly(2))
            ->method('getFieldConfig')
            ->willReturnMap([
                ['importexport', TestEntity2::class, 'imagesFieldName', $imagesFieldConfig],
                ['importexport', TestEntity3::class, 'filesFieldName', $filesFieldConfig],
            ]);

        $this->configManager->expects(self::exactly(8))
            ->method('getEntityConfig')
            ->willReturnMap([
                ['extend', TestEntity2::class, $imagesEntityConfig],
                ['extend', TestEntity3::class, $filesEntityConfig],
                ['extend', FileItem::class, $fileItemEntityConfig],
            ]);

        $this->fieldTypeHelper->expects(self::exactly(6))
            ->method('getUnderlyingType')
            ->willReturnMap([
                ['multiImage', 'oneToMany'],
                ['multiFile', 'oneToMany'],
            ]);

        $this->extension->preUpdate();

        $this->assertEquals(['is_extend' => true], $entityConfig->all());
        $this->assertEquals(['state' => ExtendScope::STATE_NEW], $fieldConfig->all());

        $imagesRelationKey = ExtendHelper::buildRelationKey(
            TestEntity2::class,
            'imagesFieldName',
            'oneToMany',
            FileItem::class
        );
        $imagesTagretFieldId = new FieldConfigId(
            'extend',
            FileItem::class,
            ExtendHelper::buildToManyRelationTargetFieldName(TestEntity2::class, 'imagesFieldName'),
            'manyToOne'
        );
        $this->assertEquals(
            [
                'is_extend' => true,
                'relation' => [
                    $imagesRelationKey => [
                        'field_id' => new FieldConfigId('extend', TestEntity2::class, 'imagesFieldName', 'oneToMany'),
                        'owner' => false,
                        'target_entity' => FileItem::class,
                        'target_field_id' => $imagesTagretFieldId,
                        'cascade' => [
                            'persist',
                            'remove',
                            'refresh',
                        ],
                        'orphanRemoval' => true,
                    ],
                ],
            ],
            $imagesEntityConfig->all()
        );

        $this->assertEquals(
            [
                'state' => ExtendScope::STATE_NEW,
                'target_entity' => FileItem::class,
                'bidirectional' => true,
                'target_grid' => ['id'],
                'target_title' => ['id'],
                'target_detailed' => ['id'],
                'relation_key' => $imagesRelationKey,
                'cascade' => [
                    'persist',
                    'remove',
                    'refresh',
                ],
                'orphanRemoval' => true,
                'full' => true,
            ],
            $imagesFieldConfig->all()
        );

        $filesRelationKey = ExtendHelper::buildRelationKey(
            TestEntity3::class,
            'filesFieldName',
            'oneToMany',
            FileItem::class
        );
        $filesTagretFieldId = new FieldConfigId(
            'extend',
            FileItem::class,
            ExtendHelper::buildToManyRelationTargetFieldName(TestEntity3::class, 'filesFieldName'),
            'manyToOne'
        );
        $this->assertEquals(
            [
                'is_extend' => true,
                'relation' => [
                    $filesRelationKey => [
                        'field_id' => new FieldConfigId('extend', TestEntity3::class, 'filesFieldName', 'oneToMany'),
                        'owner' => false,
                        'target_entity' => FileItem::class,
                        'target_field_id' => $filesTagretFieldId,
                        'cascade' => [
                            'persist',
                            'remove',
                            'refresh',
                        ],
                        'orphanRemoval' => true,
                    ],
                ],
            ],
            $filesEntityConfig->all()
        );

        $this->assertEquals(
            [
                'state' => ExtendScope::STATE_NEW,
                'target_entity' => FileItem::class,
                'bidirectional' => true,
                'target_grid' => ['id'],
                'target_title' => ['id'],
                'target_detailed' => ['id'],
                'relation_key' => $filesRelationKey,
                'cascade' => [
                    'persist',
                    'remove',
                    'refresh',
                ],
                'orphanRemoval' => true,
                'full' => true,
            ],
            $filesFieldConfig->all()
        );
    }

    private function createExtendEntityConfig(string $className): Config
    {
        $entityConfig = new Config(new EntityConfigId('extend', $className));
        $entityConfig->set('is_extend', true);

        return $entityConfig;
    }

    private function createExtendFieldConfig(string $className, string $fieldName, string $fieldType): Config
    {
        $fieldConfig = new Config(new FieldConfigId('extend', $className, $fieldName, $fieldType));
        $fieldConfig->set('state', ExtendScope::STATE_NEW);

        return $fieldConfig;
    }

    private function createImportExportFieldConfig(string $className, string $fieldName): Config
    {
        $fieldConfig = new Config(new FieldConfigId('importexport', $className, $fieldName));
        $fieldConfig->set('state', ExtendScope::STATE_NEW);

        return $fieldConfig;
    }
}
