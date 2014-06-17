<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\Tools;

use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityExtendBundle\Tools\AssociationBuildHelper;

class AssociationBuildHelperTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $entityConfigProvider;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $assocConfigProvider;

    /** @var ConfigManager|\PHPUnit_Framework_MockObject_MockObject */
    protected $configManagerMock;

    /** @var AssociationBuildHelper */
    protected $helper;

    public function setUp()
    {
        $this->configManagerMock = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Config\ConfigManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->helper = new AssociationBuildHelper($this->configManagerMock);
    }

    public function testGetScopeConfigs()
    {
        $scopeConfigs = [];

        $provider = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $provider->expects($this->once())
            ->method('getConfigs')
            ->will($this->returnValue($scopeConfigs));

        $this->configManagerMock->expecst($this->once())
            ->method('getProvider')
            ->with('test')
            ->will($this->returnValue($provider));

        $configs = $this->helper->getScopeConfigs('test');
        $this->assertSame($scopeConfigs, $configs, 'Configs returned from provider');
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
}
