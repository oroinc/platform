<?php

namespace Oro\Bundle\NoteBundle\Tests\Unit\Tools;

use Oro\Bundle\EntityConfigBundle\Config\Config;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Config\Id\EntityConfigId;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProviderInterface;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendConfigDumper;
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

        $this->configManager->expects($this->any())
            ->method('getProvider')
            ->will(
                $this->returnValueMap(
                    [
                        ['extend', $this->extendConfigProvider],
                        [NoteDumperExtension::NOTE_CONFIG_SCOPE, $this->noteConfigProvider],
                    ]
                )
            );
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
        $result = $extension->supports(ExtendConfigDumper::ACTION_PRE_UPDATE, $configs);
        $this->assertTrue($result);

        $result = $extension->supports(ExtendConfigDumper::ACTION_PRE_UPDATE, $configs);
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
        $result = $extension->supports(ExtendConfigDumper::ACTION_PRE_UPDATE, []);
        $this->assertFalse($result);

        $result = $extension->supports(ExtendConfigDumper::ACTION_POST_UPDATE, []);
        $this->assertFalse($result);
    }

    public function testPreUpdate()
    {
        $extension = $this->getMock(
            'Oro\Bundle\NoteBundle\Tools\NoteDumperExtension',
            [
                'createField',
                'addManyToOneRelation',
                'getRelationName',
                'getRelationKey',
                'getClassNamesWithFlagEnabled',
                'getConfig'
            ],
            [$this->configManager]
        );

        $configs       = [];
        $noteClassName = 'Oro\Bundle\NoteBundle\Entity\Note';
        $entityName    = 'Test\Entity';
        $withNotes     = [$entityName];
        $relationKey   = 'manyToOne|Oro\Bundle\NoteBundle\Entity\Note|Test\Entity|entity';

        $entityConfig = new Config(new EntityConfigId('extend', $entityName));
        $entityConfig->set('entity', ['label' => 'test', 'description' => 'test']);

        $extension->expects($this->once())
            ->method('getConfig')
            ->will($this->returnValue($entityConfig));

        $extension->expects($this->once())
            ->method('getClassNamesWithFlagEnabled')
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
            ->method('createField');

        $extension->expects($this->at(5))
            ->method('addManyToOneRelation')
            ->with($entityName, $noteClassName, 'entity', $relationKey);

        $extension->expects($this->at(6))
            ->method('addManyToOneRelation')
            ->with($noteClassName, $entityName, 'entity', $relationKey, true);

        $extension->preUpdate($configs);
    }

    public function testCreateField()
    {
        $extension = $this->getMock(
            'Oro\Bundle\NoteBundle\Tools\NoteDumperExtension',
            ['updateFieldConfigs'],
            [$this->configManager]
        );

        $this->configManager->expects($this->once())
            ->method('createConfigFieldModel');

        $extension->expects($this->once())
            ->method('updateFieldConfigs');

        self::callProtectedMethod(
            $extension,
            'createField',
            ['Test\Entity', 'entity', 'manyToOne', []]
        );
    }

    public function testUpdateFieldConfigs()
    {
        $extension = $this->getMock(
            'Oro\Bundle\NoteBundle\Tools\NoteDumperExtension',
            [],
            [$this->configManager]
        );

        $fieldConfig = $this->getMock('Oro\Bundle\EntityConfigBundle\Config\ConfigInterface');
        $fieldConfig->expects($this->once())
            ->method('set')
            ->with('test', true);

        $this->configManager->expects($this->once())
            ->method('persist')
            ->with($fieldConfig);

        $this->configManager->expects($this->once())
            ->method('calculateConfigChangeSet')
            ->with($fieldConfig);

        $this->extendConfigProvider->expects($this->once())
            ->method('getConfig')
            ->with('Test\Entity', 'testField')
            ->will($this->returnValue($fieldConfig));

        self::callProtectedMethod(
            $extension,
            'updateFieldConfigs',
            ['Test\Entity', 'testField', ['extend' => ['test' => true]]]
        );
    }

    public function testAddManyToOneRelation()
    {
        $extension = $this->getMock(
            'Oro\Bundle\NoteBundle\Tools\NoteDumperExtension',
            [],
            [$this->configManager]
        );

        $targetEntityName = 'Test\TargetEntity';
        $sourceEntityName = 'Test\SourceEntity';
        $relationName     = 'entity';
        $relationKey      = 'manyToOne|Test\SourceEntity|Test\TargetEntity|entity';

        // expected entity config
        $config = new Config(new EntityConfigId('extend', $targetEntityName));
        $config->set(
            'relation',
            [
                $relationKey => [
                    'assign'          => false,
                    'field_id'        => false,
                    'owner'           => false,
                    'target_entity'   => $sourceEntityName,
                    'target_field_id' => new FieldConfigId(
                        'extend',
                        'Oro\Bundle\NoteBundle\Entity\Note',
                        $relationName,
                        'manyToOne'
                    )
                ]
            ]
        );
        $config->set(
            'schema',
            [
                'relation' => [$relationKey => $relationName]
            ]
        );
        $config->set(
            'index',
            [
                'relation' => [$relationKey => null]
            ]
        );

        $this->extendConfigProvider->expects($this->once())
            ->method('getConfig')
            ->with($targetEntityName)
            ->will($this->returnValue($config));

        $this->extendConfigProvider->expects($this->once())
            ->method('persist')
            ->with($config);

        self::callProtectedMethod(
            $extension,
            'addManyToOneRelation',
            [$targetEntityName, $sourceEntityName, $relationName, $relationKey]
        );
    }

    public function testAddManyToOneRelationOwnerSide()
    {
        $extension = $this->getMock(
            'Oro\Bundle\NoteBundle\Tools\NoteDumperExtension',
            [],
            [$this->configManager]
        );

        $targetEntityName = 'Test\TargetEntity';
        $sourceEntityName = 'Test\SourceEntity';
        $relationName     = 'entity';
        $relationKey      = 'manyToOne|Test\SourceEntity|Test\TargetEntity|entity';

        // expected entity config
        $config = new Config(new EntityConfigId('extend', $targetEntityName));
        $config->set(
            'relation',
            [
                $relationKey => [
                    'assign'          => false,
                    'target_field_id' => false,
                    'owner'           => true,
                    'target_entity'   => $sourceEntityName,
                    'field_id'        => new FieldConfigId(
                        'extend',
                        'Oro\Bundle\NoteBundle\Entity\Note',
                        $relationName,
                        'manyToOne'
                    )
                ]
            ]
        );

        $this->extendConfigProvider->expects($this->once())
            ->method('getConfig')
            ->with($targetEntityName)
            ->will($this->returnValue($config));

        $this->extendConfigProvider->expects($this->once())
            ->method('persist')
            ->with($config);

        self::callProtectedMethod(
            $extension,
            'addManyToOneRelation',
            [$targetEntityName, $sourceEntityName, $relationName, $relationKey, true]
        );
    }

    public function testGetRelationKeyAndName()
    {
        $extension = $this->getMock(
            'Oro\Bundle\NoteBundle\Tools\NoteDumperExtension',
            [],
            [$this->configManager]
        );

        $keyName = self::callProtectedMethod(
            $extension,
            'getRelationKey',
            ['Test\SourceEntity', 'Test\Entity', 'entity']
        );
        $this->assertEquals('manyToOne|Test\SourceEntity|Test\Entity|entity', $keyName);

        $relationName = self::callProtectedMethod(
            $extension,
            'getRelationName',
            ['Test\SourceEntity']
        );
        $this->assertEquals('source_entity', $relationName);
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
