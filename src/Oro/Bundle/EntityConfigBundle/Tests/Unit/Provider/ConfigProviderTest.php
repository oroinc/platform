<?php

namespace Oro\Bundle\EntityConfigBundle\Tests\Unit\Provider;

use Oro\Bundle\EntityConfigBundle\Config\Config;
use Oro\Bundle\EntityConfigBundle\Config\ConfigInterface;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Config\Id\EntityConfigId;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\EntityConfigBundle\Provider\PropertyConfigBag;
use Oro\Bundle\EntityConfigBundle\Tests\Unit\Fixture\DemoEntity;

class ConfigProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject */
    private $configManager;

    /** @var ConfigProvider */
    private $configProvider;

    /** @var Config */
    private $entityConfig;

    /** @var Config */
    private $fieldConfig;

    protected function setUp()
    {
        $this->entityConfig = new Config(new EntityConfigId('testScope', DemoEntity::ENTITY_NAME));
        $this->fieldConfig  = new Config(
            new FieldConfigId('testScope', DemoEntity::ENTITY_NAME, 'testField', 'string')
        );

        $this->configManager = $this->createMock(ConfigManager::class);

        $this->configManager->expects($this->any())->method('getConfig')->will($this->returnValue($this->entityConfig));
        $this->configManager->expects($this->any())
            ->method('getEntityConfig')
            ->will($this->returnValue($this->entityConfig));
        $this->configManager->expects($this->any())->method('hasConfig')->will($this->returnValue(true));
        $this->configManager->expects($this->any())->method('persist')->will($this->returnValue(true));
        $this->configManager->expects($this->any())->method('flush')->will($this->returnValue(true));

        $this->configProvider  = new ConfigProvider(
            $this->configManager,
            'testScope',
            new PropertyConfigBag([])
        );
    }

    /**
     * @dataProvider getIdProvider
     */
    public function testGetId($className, $fieldName, $fieldType, $expectedResult)
    {
        $result = $this->configProvider->getId($className, $fieldName, $fieldType);
        $this->assertEquals($expectedResult, $result);
    }

    public function getIdProvider()
    {
        return [
            [null, null, null, new EntityConfigId('testScope')],
            ['TestCls', null, null, new EntityConfigId('testScope', 'TestCls')],
            ['TestCls', 'fieldName', 'int', new FieldConfigId('testScope', 'TestCls', 'fieldName', 'int')],
        ];
    }

    public function testConfig()
    {
        $this->assertEquals($this->configManager, $this->configProvider->getConfigManager());
        $this->assertEquals(true, $this->configProvider->hasConfig(DemoEntity::ENTITY_NAME));
        $this->assertEquals($this->entityConfig, $this->configProvider->getConfig(DemoEntity::ENTITY_NAME));
        $this->assertEquals('testScope', $this->configProvider->getScope());

        $entityConfigId = new EntityConfigId('testScope', DemoEntity::ENTITY_NAME);
        $fieldConfigId  = new FieldConfigId('testScope', DemoEntity::ENTITY_NAME, 'testField', 'string');

        $this->assertEquals($entityConfigId, $this->configProvider->getId(DemoEntity::ENTITY_NAME));
        $this->assertEquals(
            $fieldConfigId,
            $this->configProvider->getId(DemoEntity::ENTITY_NAME, 'testField', 'string')
        );

        $entityConfigIdWithOtherScope = new EntityConfigId('otherScope', DemoEntity::ENTITY_NAME);

        $this->assertEquals($this->entityConfig, $this->configProvider->getConfigById($entityConfigIdWithOtherScope));
    }

    public function testGetIds()
    {
        $this->configManager->expects($this->once())
            ->method('getIds')
            ->with('testScope', DemoEntity::ENTITY_NAME, false)
            ->will($this->returnValue([$this->entityConfig->getId()]));

        $this->assertEquals(
            [$this->entityConfig->getId()],
            $this->configProvider->getIds(DemoEntity::ENTITY_NAME)
        );
    }

    public function testGetIdsWithHidden()
    {
        $this->configManager->expects($this->once())
            ->method('getIds')
            ->with('testScope', DemoEntity::ENTITY_NAME, true)
            ->will($this->returnValue([$this->entityConfig->getId()]));

        $this->assertEquals(
            [$this->entityConfig->getId()],
            $this->configProvider->getIds(DemoEntity::ENTITY_NAME, true)
        );
    }

    public function testGetConfigs()
    {
        $this->configManager->expects($this->once())
            ->method('getConfigs')
            ->with('testScope', DemoEntity::ENTITY_NAME, false)
            ->will($this->returnValue([$this->entityConfig]));

        $this->assertEquals(
            [$this->entityConfig],
            $this->configProvider->getConfigs(DemoEntity::ENTITY_NAME)
        );
    }

    public function testGetConfigsWithHidden()
    {
        $this->configManager->expects($this->once())
            ->method('getConfigs')
            ->with('testScope', DemoEntity::ENTITY_NAME, true)
            ->will($this->returnValue([$this->entityConfig]));

        $this->assertEquals(
            [$this->entityConfig],
            $this->configProvider->getConfigs(DemoEntity::ENTITY_NAME, true)
        );
    }

    public function testMap()
    {
        $this->configManager->expects($this->once())
            ->method('getConfigs')
            ->with('testScope', DemoEntity::ENTITY_NAME, false)
            ->will($this->returnValue([$this->entityConfig]));

        $entityConfig = new Config(new EntityConfigId('testScope', DemoEntity::ENTITY_NAME));
        $entityConfig->set('key', 'value');
        $this->assertEquals(
            [$entityConfig],
            $this->configProvider->map(
                function (ConfigInterface $config) {
                    return $config->set('key', 'value');
                },
                DemoEntity::ENTITY_NAME
            )
        );
    }

    public function testFilter()
    {
        $this->configManager->expects($this->once())
            ->method('getConfigs')
            ->with('testScope', DemoEntity::ENTITY_NAME, false)
            ->will($this->returnValue([$this->entityConfig]));

        $this->assertEquals(
            [],
            $this->configProvider->filter(
                function (ConfigInterface $config) {
                    return $config->getId()->getScope() == 'wrongScope';
                },
                DemoEntity::ENTITY_NAME
            )
        );
    }
}
