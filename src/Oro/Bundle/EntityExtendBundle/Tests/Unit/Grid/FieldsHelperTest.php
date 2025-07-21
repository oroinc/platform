<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\Grid;

use Oro\Bundle\EntityBundle\EntityConfig\DatagridScope;
use Oro\Bundle\EntityConfigBundle\Config\Config;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\EntityExtendBundle\Grid\FieldsHelper;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class FieldsHelperTest extends TestCase
{
    private const ENTITY_CLASS = 'Test\Entity';
    private const FIELD_NAME = 'testFieldName';

    private ConfigManager&MockObject $configManager;
    private FieldsHelper $helper;
    private ConfigProvider&MockObject $entityConfigProvider;
    private ConfigProvider&MockObject $extendConfigProvider;
    private ConfigProvider&MockObject $datagridConfigProvider;
    private ConfigProvider&MockObject $viewConfigProvider;
    private FeatureChecker&MockObject $featureChecker;

    #[\Override]
    protected function setUp(): void
    {
        $this->configManager = $this->createMock(ConfigManager::class);
        $this->featureChecker = $this->createMock(FeatureChecker::class);

        $this->helper = new FieldsHelper(
            $this->configManager,
            $this->featureChecker
        );

        $this->entityConfigProvider = $this->createMock(ConfigProvider::class);
        $this->extendConfigProvider = $this->createMock(ConfigProvider::class);
        $this->datagridConfigProvider = $this->createMock(ConfigProvider::class);
        $this->viewConfigProvider = $this->createMock(ConfigProvider::class);

        $this->configManager->expects($this->any())
            ->method('getProvider')
            ->willReturnMap([
                ['entity', $this->entityConfigProvider],
                ['extend', $this->extendConfigProvider],
                ['datagrid', $this->datagridConfigProvider],
                ['view', $this->viewConfigProvider],
            ]);
    }

    public function testGetFieldsWithoutConfig(): void
    {
        $this->configManager->expects($this->once())
            ->method('hasConfig')
            ->with(self::ENTITY_CLASS)
            ->willReturn(false);

        $this->configManager->expects($this->never())
            ->method('getProvider');

        $this->helper->getFields(self::ENTITY_CLASS);
    }

    public function testGetFields(): void
    {
        $fieldId = new FieldConfigId('entity', self::ENTITY_CLASS, self::FIELD_NAME, 'string');

        $this->configManager->expects($this->once())
            ->method('hasConfig')
            ->with(self::ENTITY_CLASS)
            ->willReturn(true);

        $this->entityConfigProvider->expects($this->once())
            ->method('getIds')
            ->with(self::ENTITY_CLASS)
            ->willReturn([$fieldId]);

        $extendConfig = new Config(new FieldConfigId('extend', self::ENTITY_CLASS, self::FIELD_NAME, 'string'));
        $extendConfig->set('owner', ExtendScope::OWNER_CUSTOM);
        $extendConfig->set('state', ExtendScope::STATE_ACTIVE);
        $extendConfig->set('is_deleted', false);

        $datagridConfig = new Config(new FieldConfigId('datagrid', self::ENTITY_CLASS, self::FIELD_NAME, 'string'));
        $datagridConfig->set('is_visible', DatagridScope::IS_VISIBLE_TRUE);

        $this->extendConfigProvider->expects($this->once())
            ->method('getConfigById')
            ->with($fieldId)
            ->willReturn($extendConfig);

        $this->datagridConfigProvider->expects($this->once())
            ->method('getConfigById')
            ->with($fieldId)
            ->willReturn($datagridConfig);

        $viewFieldConfig = new Config(
            new FieldConfigId('view', self::ENTITY_CLASS, self::FIELD_NAME, 'string')
        );

        $this->viewConfigProvider->expects($this->any())
            ->method('getConfig')
            ->with(self::ENTITY_CLASS, self::FIELD_NAME)
            ->willReturn($viewFieldConfig);

        $fields = $this->helper->getFields(self::ENTITY_CLASS);
        $this->assertEquals([$fieldId], $fields);
    }

    public function testGetFieldsWithWrongExtendConfig(): void
    {
        $fieldId = new FieldConfigId('entity', self::ENTITY_CLASS, self::FIELD_NAME, 'string');

        $this->configManager->expects($this->once())
            ->method('hasConfig')
            ->with(self::ENTITY_CLASS)
            ->willReturn(true);

        $this->entityConfigProvider->expects($this->once())
            ->method('getIds')
            ->with(self::ENTITY_CLASS)
            ->willReturn([$fieldId]);

        $extendConfig = new Config(new FieldConfigId('extend', self::ENTITY_CLASS, self::FIELD_NAME, 'string'));
        $extendConfig->set('owner', ExtendScope::OWNER_SYSTEM);
        $extendConfig->set('state', ExtendScope::STATE_ACTIVE);
        $extendConfig->set('is_deleted', false);

        $datagridConfig = new Config(new FieldConfigId('datagrid', self::ENTITY_CLASS, self::FIELD_NAME, 'string'));
        $datagridConfig->set('is_visible', DatagridScope::IS_VISIBLE_TRUE);

        $this->extendConfigProvider->expects($this->once())
            ->method('getConfigById')
            ->with($fieldId)
            ->willReturn($extendConfig);

        $this->datagridConfigProvider->expects($this->once())
            ->method('getConfigById')
            ->willReturn($datagridConfig);

        $this->viewConfigProvider->expects($this->never())
            ->method('getConfig');

        $fields = $this->helper->getFields(self::ENTITY_CLASS);
        $this->assertEquals([], $fields);
    }

    public function testGetFieldsWhenFeatureCheckerFalse(): void
    {
        $fieldId = new FieldConfigId('entity', self::ENTITY_CLASS, self::FIELD_NAME, 'string');

        $this->configManager->expects($this->once())
            ->method('hasConfig')
            ->with(self::ENTITY_CLASS)
            ->willReturn(true);

        $this->featureChecker->expects($this->once())
            ->method('isResourceEnabled')
            ->with(self::ENTITY_CLASS, 'entities')
            ->willReturn(false);

        $this->entityConfigProvider->expects($this->once())
            ->method('getIds')
            ->with(self::ENTITY_CLASS)
            ->willReturn([$fieldId]);

        $extendConfig = new Config(new FieldConfigId('extend', self::ENTITY_CLASS, self::FIELD_NAME, 'string'));
        $extendConfig->set('owner', ExtendScope::OWNER_SYSTEM);
        $extendConfig->set('state', ExtendScope::STATE_ACTIVE);
        $extendConfig->set('is_deleted', false);
        $extendConfig->set('target_entity', self::ENTITY_CLASS);

        $datagridConfig = new Config(new FieldConfigId('datagrid', self::ENTITY_CLASS, self::FIELD_NAME, 'string'));
        $datagridConfig->set('is_visible', DatagridScope::IS_VISIBLE_TRUE);

        $this->extendConfigProvider->expects($this->once())
            ->method('getConfigById')
            ->with($fieldId)
            ->willReturn($extendConfig);

        $this->datagridConfigProvider->expects($this->once())
            ->method('getConfigById')
            ->willReturn($datagridConfig);

        $this->viewConfigProvider->expects($this->never())
            ->method('getConfig');

        $fields = $this->helper->getFields(self::ENTITY_CLASS);
        $this->assertEquals([], $fields);
    }
}
