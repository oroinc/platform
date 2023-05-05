<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\Tools\DumperExtensions;

use Oro\Bundle\EntityBundle\EntityConfig\DatagridScope;
use Oro\Bundle\EntityBundle\EntityConfig\IndexScope;
use Oro\Bundle\EntityConfigBundle\Config\Config;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Config\Id\EntityConfigId;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\EntityExtendBundle\Configuration\EntityExtendConfigurationProvider;
use Oro\Bundle\EntityExtendBundle\Extend\FieldTypeHelper;
use Oro\Bundle\EntityExtendBundle\Tools\DumperExtensions\IndexEntityConfigDumperExtension;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendConfigDumper;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class IndexEntityConfigDumperExtensionTest extends \PHPUnit\Framework\TestCase
{
    /** @var ConfigManager|\PHPUnit\Framework\MockObject\MockObject */
    private $configManager;

    /** @var IndexEntityConfigDumperExtension */
    private $extension;

    protected function setUp(): void
    {
        $this->configManager = $this->createMock(ConfigManager::class);

        $entityExtendConfigurationProvider = $this->createMock(EntityExtendConfigurationProvider::class);
        $entityExtendConfigurationProvider->expects(self::any())
            ->method('getUnderlyingTypes')
            ->willReturn(['enum' => 'manyToOne', 'multiEnum' => 'manyToMany']);

        $this->extension = new IndexEntityConfigDumperExtension(
            $this->configManager,
            new FieldTypeHelper($entityExtendConfigurationProvider)
        );
    }

    public function testSupportsPreUpdate()
    {
        $this->assertTrue(
            $this->extension->supports(ExtendConfigDumper::ACTION_PRE_UPDATE)
        );
    }

    public function testSupportsPostUpdate()
    {
        $this->assertFalse(
            $this->extension->supports(ExtendConfigDumper::ACTION_POST_UPDATE)
        );
    }

    public function testPreUpdateForNotExtendEntity()
    {
        $config = new Config(new EntityConfigId('extend', 'Test\Entity'));
        $config->set('index', ['field1' => true]);

        $extendConfigProvider = $this->createMock(ConfigProvider::class);

        $this->configManager->expects($this->once())
            ->method('getProvider')
            ->with('extend')
            ->willReturn($extendConfigProvider);
        $extendConfigProvider->expects($this->once())
            ->method('getConfigs')
            ->willReturn([$config]);

        $this->configManager->expects($this->never())
            ->method('persist');

        $this->extension->preUpdate();
    }

    public function testPreUpdateForEntityWithIsExtendAndFieldWithExtendButInvisibleInGrid()
    {
        $config = new Config(new EntityConfigId('extend', 'Test\Entity'));
        $config->set('is_extend', true);
        $config->set('index', []);

        $fieldConfig = new Config(new FieldConfigId('extend', $config->getId()->getClassName(), 'field1', 'string'));
        $fieldConfig->set('is_extend', true);

        $extendConfigProvider = $this->createMock(ConfigProvider::class);
        $datagridConfigProvider = $this->createMock(ConfigProvider::class);

        $this->configManager->expects($this->exactly(3))
            ->method('getProvider')
            ->willReturnMap([
                ['extend', $extendConfigProvider],
                ['datagrid', $datagridConfigProvider],
            ]);
        $extendConfigProvider->expects($this->exactly(2))
            ->method('getConfigs')
            ->willReturnCallback(function ($className) use ($config, $fieldConfig) {
                if (empty($className)) {
                    return [$config];
                }

                return [$fieldConfig];
            });
        $datagridConfigProvider->expects($this->once())
            ->method('hasConfig')
            ->with($config->getId()->getClassName(), 'field1')
            ->willReturn(false);

        $this->configManager->expects($this->never())
            ->method('persist');

        $this->extension->preUpdate();
    }

    public function testPreUpdateForEntityWithIsExtendAndNotExtendField()
    {
        $config = new Config(new EntityConfigId('extend', 'Test\Entity'));
        $config->set('is_extend', true);
        $config->set('index', []);

        $fieldConfig = new Config(new FieldConfigId('extend', $config->getId()->getClassName(), 'field1', 'string'));

        $extendConfigProvider = $this->createMock(ConfigProvider::class);
        $datagridConfigProvider = $this->createMock(ConfigProvider::class);

        $this->configManager->expects($this->exactly(2))
            ->method('getProvider')
            ->willReturnMap([
                ['extend', $extendConfigProvider],
                ['datagrid', $datagridConfigProvider],
            ]);
        $extendConfigProvider->expects($this->exactly(2))
            ->method('getConfigs')
            ->willReturnCallback(function ($className) use ($config, $fieldConfig) {
                if (empty($className)) {
                    return [$config];
                }

                return [$fieldConfig];
            });

        $this->configManager->expects($this->never())
            ->method('persist');

        $this->extension->preUpdate();
    }

    public function testPreUpdateForEntityWithExtendAndFieldWithExtendButInvisibleInGrid()
    {
        $config = new Config(new EntityConfigId('extend', 'Test\Entity'));
        $config->set('is_extend', true);
        $config->set('index', []);

        $fieldConfig = new Config(new FieldConfigId('extend', $config->getId()->getClassName(), 'field1', 'string'));
        $fieldConfig->set('is_extend', true);

        $extendConfigProvider = $this->createMock(ConfigProvider::class);
        $datagridConfigProvider = $this->createMock(ConfigProvider::class);

        $this->configManager->expects($this->exactly(3))
            ->method('getProvider')
            ->willReturnMap([
                ['extend', $extendConfigProvider],
                ['datagrid', $datagridConfigProvider],
            ]);
        $extendConfigProvider->expects($this->exactly(2))
            ->method('getConfigs')
            ->willReturnCallback(function ($className) use ($config, $fieldConfig) {
                if (empty($className)) {
                    return [$config];
                }

                return [$fieldConfig];
            });
        $datagridConfigProvider->expects($this->once())
            ->method('hasConfig')
            ->with($config->getId()->getClassName(), 'field1')
            ->willReturn(false);

        $this->configManager->expects($this->never())
            ->method('persist');

        $this->extension->preUpdate();
    }

    public function testPreUpdateForNewIndexedField()
    {
        $config = new Config(new EntityConfigId('extend', 'Test\Entity'));
        $config->set('is_extend', true);
        $config->set('index', []);

        $fieldConfig = new Config(new FieldConfigId('extend', $config->getId()->getClassName(), 'field1', 'string'));
        $fieldConfig->set('is_extend', true);

        $fieldConfig2 = new Config(new FieldConfigId('extend', $config->getId()->getClassName(), 'field2', 'string'));
        $fieldConfig2->set('is_extend', true);
        $fieldConfig2->set('unique', true);

        $datagridFieldConfig = new Config(
            new FieldConfigId('datagrid', $config->getId()->getClassName(), 'field1', 'string')
        );
        $datagridFieldConfig->set('is_visible', DatagridScope::IS_VISIBLE_TRUE);

        $datagridFieldConfig2 = new Config(
            new FieldConfigId('datagrid', $config->getId()->getClassName(), 'field2', 'string')
        );
        $datagridFieldConfig2->set('is_visible', DatagridScope::IS_VISIBLE_TRUE);

        $extendConfigProvider = $this->createMock(ConfigProvider::class);
        $datagridConfigProvider = $this->createMock(ConfigProvider::class);

        $this->configManager->expects($this->exactly(4))
            ->method('getProvider')
            ->willReturnMap([
                ['extend', $extendConfigProvider],
                ['datagrid', $datagridConfigProvider],
            ]);
        $extendConfigProvider->expects($this->exactly(2))
            ->method('getConfigs')
            ->willReturnCallback(function ($className) use ($config, $fieldConfig, $fieldConfig2) {
                if (empty($className)) {
                    return [$config];
                }

                return [$fieldConfig, $fieldConfig2];
            });
        $datagridConfigProvider->expects($this->exactly(2))
            ->method('hasConfig')
            ->willReturnMap([
                [$datagridFieldConfig->getId()->getClassName(), $datagridFieldConfig->getId()->getFieldName(), true],
                [$datagridFieldConfig2->getId()->getClassName(), $datagridFieldConfig2->getId()->getFieldName(), true]
            ]);

        $datagridConfigProvider->expects($this->exactly(2))
            ->method('getConfig')
            ->willReturnMap([
                [
                    $datagridFieldConfig->getId()->getClassName(),
                    $datagridFieldConfig->getId()->getFieldName(),
                    $datagridFieldConfig
                ],
                [
                    $datagridFieldConfig2->getId()->getClassName(),
                    $datagridFieldConfig2->getId()->getFieldName(),
                    $datagridFieldConfig2
                ]
            ]);

        $this->configManager->expects($this->once())
            ->method('persist')
            ->with($this->identicalTo($config));

        $this->extension->preUpdate();

        $this->assertEquals(
            [
                'field1' => IndexScope::INDEX_SIMPLE,
                'field2' => IndexScope::INDEX_UNIQUE
            ],
            $config->get('index')
        );
    }

    /**
     * @dataProvider preUpdateForRelationsProvider
     */
    public function testPreUpdateForRelations(string $fieldType)
    {
        $config = new Config(new EntityConfigId('extend', 'Test\Entity'));
        $config->set('is_extend', true);
        $config->set('index', []);

        $fieldConfig = new Config(new FieldConfigId('extend', $config->getId()->getClassName(), 'field1', $fieldType));
        $fieldConfig->set('is_extend', true);

        $extendConfigProvider = $this->createMock(ConfigProvider::class);

        $this->configManager->expects($this->exactly(2))
            ->method('getProvider')
            ->with('extend')
            ->willReturn($extendConfigProvider);
        $extendConfigProvider->expects($this->exactly(2))
            ->method('getConfigs')
            ->willReturnCallback(function ($className) use ($config, $fieldConfig) {
                if (empty($className)) {
                    return [$config];
                }

                return [$fieldConfig];
            });

        $this->configManager->expects($this->never())
            ->method('persist');

        $this->extension->preUpdate();
    }

    public function preUpdateForRelationsProvider(): array
    {
        return [
            ['manyToOne'],
            ['oneToMany'],
            ['manyToMany'],
            ['enum'],
            ['multiEnum'],
        ];
    }

    public function testPreUpdateForRemoveIndexedField()
    {
        $config = new Config(new EntityConfigId('extend', 'Test\Entity'));
        $config->set('is_extend', true);
        $config->set('index', ['field1' => true]);

        $fieldConfig = new Config(new FieldConfigId('extend', $config->getId()->getClassName(), 'field1', 'string'));
        $fieldConfig->set('is_extend', true);

        $datagridFieldConfig = new Config(
            new FieldConfigId('datagrid', $config->getId()->getClassName(), 'field1', 'string')
        );

        $extendConfigProvider = $this->createMock(ConfigProvider::class);
        $datagridConfigProvider = $this->createMock(ConfigProvider::class);

        $this->configManager->expects($this->exactly(3))
            ->method('getProvider')
            ->willReturnMap([
                ['extend', $extendConfigProvider],
                ['datagrid', $datagridConfigProvider],
            ]);
        $extendConfigProvider->expects($this->exactly(2))
            ->method('getConfigs')
            ->willReturnCallback(function ($className) use ($config, $fieldConfig) {
                if (empty($className)) {
                    return [$config];
                }

                return [$fieldConfig];
            });

        $datagridConfigProvider->expects($this->once())
            ->method('hasConfig')
            ->with($datagridFieldConfig->getId()->getClassName(), $datagridFieldConfig->getId()->getFieldName())
            ->willReturn(true);
        $datagridConfigProvider->expects($this->once())
            ->method('getConfig')
            ->with($datagridFieldConfig->getId()->getClassName(), $datagridFieldConfig->getId()->getFieldName())
            ->willReturn($datagridFieldConfig);

        $this->configManager->expects($this->once())
            ->method('persist')
            ->with($this->identicalTo($config));

        $this->extension->preUpdate();

        $this->assertFalse(
            $config->has('index')
        );
    }

    public function testPreUpdateForNoChanges()
    {
        $config = new Config(new EntityConfigId('extend', 'Test\Entity1'));
        $config->set('is_extend', true);
        $config->set('index', ['field1' => true]);

        $fieldConfig1 = new Config(new FieldConfigId('extend', $config->getId()->getClassName(), 'field1', 'string'));
        $fieldConfig1->set('is_extend', true);
        $fieldConfig2 = new Config(new FieldConfigId('extend', $config->getId()->getClassName(), 'field2', 'string'));
        $fieldConfig2->set('is_extend', true);

        $datagridFieldConfig1= new Config(
            new FieldConfigId('datagrid', $config->getId()->getClassName(), 'field1', 'string')
        );
        $datagridFieldConfig1->set('is_visible', DatagridScope::IS_VISIBLE_TRUE);
        $datagridFieldConfig2= new Config(
            new FieldConfigId('datagrid', $config->getId()->getClassName(), 'field2', 'string')
        );

        $extendConfigProvider = $this->createMock(ConfigProvider::class);
        $datagridConfigProvider = $this->createMock(ConfigProvider::class);

        $this->configManager->expects($this->exactly(4))
            ->method('getProvider')
            ->willReturnMap([
                ['extend', $extendConfigProvider],
                ['datagrid', $datagridConfigProvider],
            ]);
        $extendConfigProvider->expects($this->exactly(2))
            ->method('getConfigs')
            ->willReturnCallback(function ($className) use ($config, $fieldConfig1, $fieldConfig2) {
                if (empty($className)) {
                    return [$config];
                }

                return [$fieldConfig1, $fieldConfig2];
            });

        $datagridConfigProvider->expects($this->exactly(2))
            ->method('hasConfig')
            ->willReturnMap([
                [$datagridFieldConfig1->getId()->getClassName(), $datagridFieldConfig1->getId()->getFieldName(), true],
                [$datagridFieldConfig2->getId()->getClassName(), $datagridFieldConfig2->getId()->getFieldName(), true]
            ]);
        $datagridConfigProvider->expects($this->exactly(2))
            ->method('getConfig')
            ->willReturnMap([
                [
                    $datagridFieldConfig1->getId()->getClassName(),
                    $datagridFieldConfig1->getId()->getFieldName(),
                    $datagridFieldConfig1
                ],
                [
                    $datagridFieldConfig2->getId()->getClassName(),
                    $datagridFieldConfig2->getId()->getFieldName(),
                    $datagridFieldConfig2
                ]
            ]);

        $this->configManager->expects($this->never())
            ->method('persist');

        $this->extension->preUpdate();
    }
}
