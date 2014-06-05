<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\Tools;

use Oro\Bundle\EntityConfigBundle\Config\Config;
use Oro\Bundle\EntityConfigBundle\Config\Id\EntityConfigId;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendConfigDumper;

class AssociationExtendConfigDumperExtensionTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $extendConfigProvider;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $entityConfigProvider;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $assocConfigProvider;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $configManager;

    public function setUp()
    {
        $this->extendConfigProvider = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $this->entityConfigProvider = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $this->assocConfigProvider = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider')
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
                        ['entity', $this->entityConfigProvider],
                        ['assoc', $this->assocConfigProvider],
                    ]
                )
            );
    }

    public function testSupportsTrue()
    {
        $extension = $this->getExtensionMock();

        $assocConfig = new Config(new EntityConfigId('assoc', 'Test\Entity'));
        $assocConfig->set('enabled', 1);

        $this->assocConfigProvider->expects($this->once())
            ->method('getConfigs')
            ->will($this->returnValue([$assocConfig]));

        $result = $extension->supports(ExtendConfigDumper::ACTION_PRE_UPDATE);
        $this->assertTrue($result);

        $result = $extension->supports(ExtendConfigDumper::ACTION_POST_UPDATE);
        $this->assertFalse($result);
    }

    public function testSupportsFalse()
    {
        $extension = $this->getExtensionMock();

        $assocConfig = new Config(new EntityConfigId('assoc', 'Test\Entity'));

        $this->assocConfigProvider->expects($this->once())
            ->method('getConfigs')
            ->will($this->returnValue([$assocConfig]));

        $result = $extension->supports(ExtendConfigDumper::ACTION_PRE_UPDATE);
        $this->assertFalse($result);

        $result = $extension->supports(ExtendConfigDumper::ACTION_POST_UPDATE);
        $this->assertFalse($result);
    }

    public function testPreUpdate()
    {
        $extension = $this->getExtensionMock(
            [
                'createField',
                'addManyToOneRelation',
                'addManyToOneRelationTargetSide',
            ]
        );

        $configs        = [];
        $assocClassName = 'Test\AssocEntity';
        $targetEntity   = 'Test\Entity';
        $relationKey    = 'manyToOne|Test\AssocEntity|Test\Entity|entity';
        $relationName   = 'entity';

        $assocConfig = new Config(new EntityConfigId('assoc', $targetEntity));
        $assocConfig->set('enabled', true);
        $this->assocConfigProvider->expects($this->once())
            ->method('getConfigs')
            ->will($this->returnValue([$assocConfig]));

        $entityConfig = new Config(new EntityConfigId('entity', $targetEntity));
        $entityConfig->set('label', 'test');
        $entityConfig->set('description', 'test');
        $this->entityConfigProvider->expects($this->once())
            ->method('getConfig')
            ->with($targetEntity)
            ->will($this->returnValue($entityConfig));

        $extension->expects($this->once())
            ->method('createField');

        $extension->expects($this->once())
            ->method('addManyToOneRelation')
            ->with($targetEntity, $assocClassName, $relationName, $relationKey);

        $extension->expects($this->once())
            ->method('addManyToOneRelationTargetSide')
            ->with($targetEntity, $assocClassName, $relationName, $relationKey);

        $extension->preUpdate($configs);
    }

    public function testCreateField()
    {
        $extension = $this->getExtensionMock(
            [
                'updateFieldConfigs'
            ]
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
        $extension = $this->getExtensionMock();

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
        $extension = $this->getExtensionMock();

        $targetEntityName = 'Test\TargetEntity';
        $sourceEntityName = 'Test\SourceEntity';
        $relationName     = 'entity';
        $relationKey      = 'manyToOne|Test\SourceEntity|Test\TargetEntity|entity';

        $extendConfig = new Config(new EntityConfigId('extend', $sourceEntityName));

        $expectedExtendConfig = new Config(new EntityConfigId('extend', $sourceEntityName));
        $expectedExtendConfig->set(
            'relation',
            [
                $relationKey => [
                    'assign'          => false,
                    'field_id'        => new FieldConfigId('extend', $sourceEntityName, $relationName, 'manyToOne'),
                    'owner'           => true,
                    'target_entity'   => $targetEntityName,
                    'target_field_id' => false
                ]
            ]
        );
        $expectedExtendConfig->set(
            'schema',
            ['relation' => [$relationName => $relationName]]
        );
        $expectedExtendConfig->set(
            'index',
            [$relationName => null]
        );

        $this->extendConfigProvider->expects($this->once())
            ->method('getConfig')
            ->with($sourceEntityName)
            ->will($this->returnValue($extendConfig));

        $this->extendConfigProvider->expects($this->once())
            ->method('persist')
            ->with($this->identicalTo($extendConfig));

        self::callProtectedMethod(
            $extension,
            'addManyToOneRelation',
            [$targetEntityName, $sourceEntityName, $relationName, $relationKey]
        );

        $this->assertEquals($expectedExtendConfig, $extendConfig);
    }

    public function testAddManyToOneRelationTargetSide()
    {
        $extension = $this->getExtensionMock();

        $targetEntityName = 'Test\TargetEntity';
        $sourceEntityName = 'Test\SourceEntity';
        $relationName     = 'entity';
        $relationKey      = 'manyToOne|Test\SourceEntity|Test\TargetEntity|entity';

        $extendConfig = new Config(new EntityConfigId('extend', $targetEntityName));

        $expectedExtendConfig = new Config(new EntityConfigId('extend', $targetEntityName));
        $expectedExtendConfig->set(
            'relation',
            [
                $relationKey => [
                    'assign'          => false,
                    'target_field_id' => new FieldConfigId('extend', $sourceEntityName, $relationName, 'manyToOne'),
                    'owner'           => false,
                    'target_entity'   => $sourceEntityName,
                    'field_id'        => false
                ]
            ]
        );

        $this->extendConfigProvider->expects($this->once())
            ->method('getConfig')
            ->with($targetEntityName)
            ->will($this->returnValue($extendConfig));

        $this->extendConfigProvider->expects($this->once())
            ->method('persist')
            ->with($this->identicalTo($extendConfig));

        self::callProtectedMethod(
            $extension,
            'addManyToOneRelationTargetSide',
            [$targetEntityName, $sourceEntityName, $relationName, $relationKey]
        );

        $this->assertEquals($expectedExtendConfig, $extendConfig);
    }

    /**
     * @param  mixed  $obj
     * @param  string $methodName
     * @param  array  $args
     * @return mixed
     */
    public static function callProtectedMethod($obj, $methodName, array $args)
    {
        $class  = new \ReflectionClass($obj);
        $method = $class->getMethod($methodName);
        $method->setAccessible(true);

        return $method->invokeArgs($obj, $args);
    }

    protected function getExtensionMock(array $methods = [])
    {
        $extension = $this->getMockForAbstractClass(
            'Oro\Bundle\EntityExtendBundle\Tools\AssociationExtendConfigDumperExtension',
            [$this->configManager],
            '',
            true,
            true,
            true,
            $methods
        );
        $extension->expects($this->any())
            ->method('getAssociationEntityClass')
            ->will($this->returnValue('Test\AssocEntity'));
        $extension->expects($this->any())
            ->method('getAssociationScope')
            ->will($this->returnValue('assoc'));

        return $extension;
    }
}
