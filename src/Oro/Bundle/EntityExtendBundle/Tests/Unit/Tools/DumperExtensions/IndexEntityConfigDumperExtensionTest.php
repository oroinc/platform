<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\Tools\DumperExtensions;

use Oro\Bundle\EntityBundle\EntityConfig\DatagridScope;
use Oro\Bundle\EntityConfigBundle\Config\Config;
use Oro\Bundle\EntityConfigBundle\Config\Id\EntityConfigId;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityExtendBundle\Extend\FieldTypeHelper;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendConfigDumper;
use Oro\Bundle\EntityExtendBundle\Tools\DumperExtensions\IndexEntityConfigDumperExtension;

class IndexEntityConfigDumperExtensionTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $configManager;

    /** @var IndexEntityConfigDumperExtension */
    protected $extension;

    public function setUp()
    {
        $this->configManager = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Config\ConfigManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->extension = new IndexEntityConfigDumperExtension(
            $this->configManager,
            new FieldTypeHelper(['enum' => 'manyToOne', 'multiEnum' => 'manyToMany'])
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

        $extendConfigProvider = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $this->configManager->expects($this->once())
            ->method('getProvider')
            ->with('extend')
            ->will($this->returnValue($extendConfigProvider));
        $extendConfigProvider->expects($this->once())
            ->method('getConfigs')
            ->will($this->returnValue([$config]));

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

        $extendConfigProvider = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider')
            ->disableOriginalConstructor()
            ->getMock();
        $datagridConfigProvider = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $this->configManager->expects($this->exactly(3))
            ->method('getProvider')
            ->will(
                $this->returnValueMap(
                    [
                        ['extend', $extendConfigProvider],
                        ['datagrid', $datagridConfigProvider],
                    ]
                )
            );
        $extendConfigProvider->expects($this->exactly(2))
            ->method('getConfigs')
            ->will(
                $this->returnCallback(
                    function ($className) use ($config, $fieldConfig) {
                        if (empty($className)) {
                            return [$config];
                        }

                        return [$fieldConfig];
                    }
                )
            );
        $datagridConfigProvider->expects($this->once())
            ->method('hasConfig')
            ->with($config->getId()->getClassName(), 'field1')
            ->will($this->returnValue(false));

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

        $extendConfigProvider = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider')
            ->disableOriginalConstructor()
            ->getMock();
        $datagridConfigProvider = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $this->configManager->expects($this->exactly(2))
            ->method('getProvider')
            ->will(
                $this->returnValueMap(
                    [
                        ['extend', $extendConfigProvider],
                        ['datagrid', $datagridConfigProvider],
                    ]
                )
            );
        $extendConfigProvider->expects($this->exactly(2))
            ->method('getConfigs')
            ->will(
                $this->returnCallback(
                    function ($className) use ($config, $fieldConfig) {
                        if (empty($className)) {
                            return [$config];
                        }

                        return [$fieldConfig];
                    }
                )
            );

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

        $extendConfigProvider = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider')
            ->disableOriginalConstructor()
            ->getMock();
        $datagridConfigProvider = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $this->configManager->expects($this->exactly(3))
            ->method('getProvider')
            ->will(
                $this->returnValueMap(
                    [
                        ['extend', $extendConfigProvider],
                        ['datagrid', $datagridConfigProvider],
                    ]
                )
            );
        $extendConfigProvider->expects($this->exactly(2))
            ->method('getConfigs')
            ->will(
                $this->returnCallback(
                    function ($className) use ($config, $fieldConfig) {
                        if (empty($className)) {
                            return [$config];
                        }

                        return [$fieldConfig];
                    }
                )
            );
        $datagridConfigProvider->expects($this->once())
            ->method('hasConfig')
            ->with($config->getId()->getClassName(), 'field1')
            ->will($this->returnValue(false));

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

        $datagridFieldConfig = new Config(
            new FieldConfigId('datagrid', $config->getId()->getClassName(), 'field1', 'string')
        );
        $datagridFieldConfig->set('is_visible', DatagridScope::IS_VISIBLE_TRUE);

        $extendConfigProvider = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider')
            ->disableOriginalConstructor()
            ->getMock();
        $datagridConfigProvider = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $this->configManager->expects($this->exactly(3))
            ->method('getProvider')
            ->will(
                $this->returnValueMap(
                    [
                        ['extend', $extendConfigProvider],
                        ['datagrid', $datagridConfigProvider],
                    ]
                )
            );
        $extendConfigProvider->expects($this->exactly(2))
            ->method('getConfigs')
            ->will(
                $this->returnCallback(
                    function ($className) use ($config, $fieldConfig) {
                        if (empty($className)) {
                            return [$config];
                        }

                        return [$fieldConfig];
                    }
                )
            );
        $datagridConfigProvider->expects($this->once())
            ->method('hasConfig')
            ->with($datagridFieldConfig->getId()->getClassName(), $datagridFieldConfig->getId()->getFieldName())
            ->will($this->returnValue(true));
        $datagridConfigProvider->expects($this->once())
            ->method('getConfig')
            ->with($datagridFieldConfig->getId()->getClassName(), $datagridFieldConfig->getId()->getFieldName())
            ->will($this->returnValue($datagridFieldConfig));

        $this->configManager->expects($this->once())
            ->method('persist')
            ->with($this->identicalTo($config));

        $this->extension->preUpdate();

        $this->assertEquals(
            ['field1' => true],
            $config->get('index')
        );
    }

    /**
     * @dataProvider preUpdateForRelationsProvider
     */
    public function testPreUpdateForRelations($fieldType)
    {
        $config = new Config(new EntityConfigId('extend', 'Test\Entity'));
        $config->set('is_extend', true);
        $config->set('index', []);

        $fieldConfig = new Config(new FieldConfigId('extend', $config->getId()->getClassName(), 'field1', $fieldType));
        $fieldConfig->set('is_extend', true);

        $extendConfigProvider = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $this->configManager->expects($this->exactly(2))
            ->method('getProvider')
            ->with('extend')
            ->will($this->returnValue($extendConfigProvider));
        $extendConfigProvider->expects($this->exactly(2))
            ->method('getConfigs')
            ->will(
                $this->returnCallback(
                    function ($className) use ($config, $fieldConfig) {
                        if (empty($className)) {
                            return [$config];
                        }

                        return [$fieldConfig];
                    }
                )
            );

        $this->configManager->expects($this->never())
            ->method('persist');

        $this->extension->preUpdate();
    }

    public function preUpdateForRelationsProvider()
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

        $extendConfigProvider = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider')
            ->disableOriginalConstructor()
            ->getMock();
        $datagridConfigProvider = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $this->configManager->expects($this->exactly(3))
            ->method('getProvider')
            ->will(
                $this->returnValueMap(
                    [
                        ['extend', $extendConfigProvider],
                        ['datagrid', $datagridConfigProvider],
                    ]
                )
            );
        $extendConfigProvider->expects($this->exactly(2))
            ->method('getConfigs')
            ->will(
                $this->returnCallback(
                    function ($className) use ($config, $fieldConfig) {
                        if (empty($className)) {
                            return [$config];
                        }

                        return [$fieldConfig];
                    }
                )
            );

        $datagridConfigProvider->expects($this->once())
            ->method('hasConfig')
            ->with($datagridFieldConfig->getId()->getClassName(), $datagridFieldConfig->getId()->getFieldName())
            ->will($this->returnValue(true));
        $datagridConfigProvider->expects($this->once())
            ->method('getConfig')
            ->with($datagridFieldConfig->getId()->getClassName(), $datagridFieldConfig->getId()->getFieldName())
            ->will($this->returnValue($datagridFieldConfig));

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

        $extendConfigProvider = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider')
            ->disableOriginalConstructor()
            ->getMock();
        $datagridConfigProvider = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $this->configManager->expects($this->exactly(4))
            ->method('getProvider')
            ->will(
                $this->returnValueMap(
                    [
                        ['extend', $extendConfigProvider],
                        ['datagrid', $datagridConfigProvider],
                    ]
                )
            );
        $extendConfigProvider->expects($this->exactly(2))
            ->method('getConfigs')
            ->will(
                $this->returnCallback(
                    function ($className) use ($config, $fieldConfig1, $fieldConfig2) {
                        if (empty($className)) {
                            return [$config];
                        }

                        return [$fieldConfig1, $fieldConfig2];
                    }
                )
            );

        $datagridConfigProvider->expects($this->at(0))
            ->method('hasConfig')
            ->with($datagridFieldConfig1->getId()->getClassName(), $datagridFieldConfig1->getId()->getFieldName())
            ->will($this->returnValue(true));
        $datagridConfigProvider->expects($this->at(1))
            ->method('getConfig')
            ->with($datagridFieldConfig1->getId()->getClassName(), $datagridFieldConfig1->getId()->getFieldName())
            ->will($this->returnValue($datagridFieldConfig1));
        $datagridConfigProvider->expects($this->at(2))
            ->method('hasConfig')
            ->with($datagridFieldConfig2->getId()->getClassName(), $datagridFieldConfig2->getId()->getFieldName())
            ->will($this->returnValue(true));
        $datagridConfigProvider->expects($this->at(3))
            ->method('getConfig')
            ->with($datagridFieldConfig2->getId()->getClassName(), $datagridFieldConfig2->getId()->getFieldName())
            ->will($this->returnValue($datagridFieldConfig2));

        $this->configManager->expects($this->never())
            ->method('persist');

        $this->extension->preUpdate();
    }
}
