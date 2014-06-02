<?php

namespace Oro\Bundle\NoteBundle\Tests\Unit\Tools;

use Oro\Bundle\EntityConfigBundle\Config\Config;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Config\Id\EntityConfigId;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProviderInterface;
use Oro\Bundle\EntityExtendBundle\Tools\ConfigDumperExtension;
use Oro\Bundle\NoteBundle\Tools\NoteDumperExtension;

class NoteDumperExtensionTest extends \PHPUnit_Framework_TestCase
{
    /** @var ConfigProviderInterface|\PHPUnit_Framework_MockObject_MockObject */
    protected $extendConfigProvider;

    /** @var ConfigProviderInterface|\PHPUnit_Framework_MockObject_MockObject */
    protected $noteConfigProvider;

    /** @var ConfigManager|\PHPUnit_Framework_MockObject_MockObject */
    protected $configManager;

    public function setUp()
    {
        $this->extendConfigProvider = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $this->noteConfigProvider = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $this->configManager = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Config\ConfigManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->configManager->expects($this->at(0))
            ->method('getProvider')
            ->with('extend')
            ->will($this->returnValue($this->extendConfigProvider));

        $this->configManager->expects($this->at(1))
            ->method('getProvider')
            ->with(NoteDumperExtension::NOTE_CONFIG_SCOPE)
            ->will($this->returnValue($this->noteConfigProvider));
    }

    /**
     * Tests supports method
     */
    public function testSupportsTrue()
    {
        $configs = ['not-checked-option' => 'anything'];

        $entityConfig   = new Config(new EntityConfigId('extend', 'Test\Entity'));
        $entityConfig->set('enabled', 1);
        $noteConfigs = [
            $entityConfig
        ];

        $this->noteConfigProvider->expects($this->once())
            ->method('getConfigs')
            ->will($this->returnValue($noteConfigs));

        $extension = new NoteDumperExtension($this->configManager);
        $result = $extension->supports(ConfigDumperExtension::ACTION_PRE_UPDATE, $this->extendConfigProvider, $configs);
        $this->assertTrue($result);

        $result = $extension->supports(ConfigDumperExtension::ACTION_PRE_UPDATE, $this->extendConfigProvider, $configs);
        $this->assertTrue($result);
    }

    /**
     * Test extension not supported
     */
    public function testSupportsFalse()
    {
        $noteConfigs = [
            new Config(new EntityConfigId('extend', 'Test\Entity'))
        ];

        $this->noteConfigProvider->expects($this->once())
            ->method('getConfigs')
            ->will($this->returnValue($noteConfigs));

        $extension = new NoteDumperExtension($this->configManager);
        $result = $extension->supports(ConfigDumperExtension::ACTION_PRE_UPDATE, $this->extendConfigProvider, []);
        $this->assertFalse($result);

        $result = $extension->supports(ConfigDumperExtension::ACTION_POST_UPDATE, $this->extendConfigProvider, []);
        $this->assertFalse($result);
    }

    public function testPreUpdate()
    {
        $extension = $this->getMock(
            'Oro\Bundle\NoteBundle\Tools\NoteDumperExtension',
            ['createField', 'addManyToOneRelation', 'getRelationName', 'getRelationKey', 'getNotesEnabledFor'],
            [$this->configManager]
        );

        $configs       = [];
        $noteClassName = 'Oro\Bundle\NoteBundle\Entity\Note';
        $entityName    = 'Test\Entity';
        $withNotes     = [$entityName];
        $relationKey   = 'manyToOne|Oro\Bundle\NoteBundle\Entity\Note|Test\Entity|entity';

        $extension->expects($this->once())
            ->method('getNotesEnabledFor')
            ->will($this->returnValue($withNotes));

        $extension->expects($this->once())
            ->method('getRelationName')
            ->with($entityName)
            ->will($this->returnValue('entity'));

        $extension->expects($this->once())
            ->method('getRelationKey')
            ->with($noteClassName, $entityName, 'entity')
            ->will($this->returnValue($relationKey));

        $extension->expects($this->once())
            ->method('createField')
            ->with($noteClassName, 'entity', 'manyToOne', $entityName, $relationKey);

        $extension->expects($this->at(4))
            ->method('addManyToOneRelation')
            ->with($entityName, $noteClassName, 'entity', $relationKey);

        $extension->expects($this->at(5))
            ->method('addManyToOneRelation')
            ->with($noteClassName, $entityName, 'entity', $relationKey, true);

        $extension->preUpdate($this->extendConfigProvider, $configs);
    }

    public function testCreateField()
    {
        $extension = $this->getMock(
            'Oro\Bundle\NoteBundle\Tools\NoteDumperExtension',
            ['updateFieldConfig'],
            [$this->configManager]
        );

        $cm = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Config\ConfigManager')
            ->disableOriginalConstructor()
            ->getMock();

        $cm->expects($this->once())
            ->method('createConfigFieldModel');

        $this->extendConfigProvider->expects($this->once())
            ->method('getConfigManager')
            ->will($this->returnValue($cm));

        $extension->expects($this->exactly(5))
            ->method('updateFieldConfig');

        self::callProtectedMethod(
            $extension,
            'createField',
            ['Test\Entity', 'entity', 'manyToOne', 'Test\Entity2', 'rel-key']
        );
    }

    public function testUpdateFieldConfig()
    {
        $extension = $this->getMock(
            'Oro\Bundle\NoteBundle\Tools\NoteDumperExtension',
            [],
            [$this->configManager]
        );

        $cm = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Config\ConfigManager')
            ->disableOriginalConstructor()
            ->getMock();

        $configProvider = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $cm->expects($this->once())
            ->method('getProvider')
            ->with('extend')
            ->will($this->returnValue($configProvider));

        $fieldConfig = $this->getMock('Oro\Bundle\EntityConfigBundle\Config\ConfigInterface');
        $fieldConfig->expects($this->once())
            ->method('set')
            ->with('test', true);

        $cm->expects($this->once())
            ->method('persist')
            ->with($fieldConfig);

        $cm->expects($this->once())
            ->method('calculateConfigChangeSet')
            ->with($fieldConfig);

        $configProvider->expects($this->once())
            ->method('getConfig')
            ->with('Test\Entity', 'entity')
            ->will($this->returnValue($fieldConfig));



        self::callProtectedMethod(
            $extension,
            'updateFieldConfig',
            [$cm, 'extend', 'Test\Entity', 'entity', ['test' => true]]
        );
    }

    /**
     * @param  mixed  $obj
     * @param  string $methodName
     * @param  array  $args
     * @return mixed
     */
    public static function callProtectedMethod($obj, $methodName, array $args)
    {
        $class = new \ReflectionClass($obj);
        $method = $class->getMethod($methodName);
        $method->setAccessible(true);

        return $method->invokeArgs($obj, $args);
    }
}
