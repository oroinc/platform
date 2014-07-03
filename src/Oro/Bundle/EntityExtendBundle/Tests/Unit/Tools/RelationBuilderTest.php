<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\Tools;

use Oro\Bundle\EntityConfigBundle\Config\Config;
use Oro\Bundle\EntityConfigBundle\Config\Id\EntityConfigId;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityExtendBundle\Tools\RelationBuilder;

class RelationBuilderTest extends \PHPUnit_Framework_TestCase
{
    const SOURCE_CLASS = 'Test\SourceEntity';
    const TARGET_CLASS = 'Test\TargetEntity';

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $configManager;

    /** @var RelationBuilder */
    protected $builder;

    public function setUp()
    {
        $this->configManager   = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Config\ConfigManager')
            ->disableOriginalConstructor()
            ->getMock();
        $this->builder = new RelationBuilder($this->configManager);
    }

    public function testAddFieldConfig()
    {
        $this->configManager->expects($this->once())
            ->method('createConfigFieldModel')
            ->with('Test\Entity', 'testField', 'manyToOne');

        $fieldConfig = $this->getMock('Oro\Bundle\EntityConfigBundle\Config\ConfigInterface');
        $fieldConfig->expects($this->once())
            ->method('set')
            ->with('test', true);

        $extendConfigProvider = $this->getConfigProviderMock();
        $extendConfigProvider->expects($this->once())
            ->method('getConfig')
            ->with('Test\Entity', 'testField')
            ->will($this->returnValue($fieldConfig));
        $extendConfigProvider->expects($this->once())
            ->method('persist')
            ->with($fieldConfig);

        $this->configManager->expects($this->any())
            ->method('getProvider')
            ->will(
                $this->returnValueMap(
                    [
                        ['extend', $extendConfigProvider]
                    ]
                )
            );
        $this->configManager->expects($this->once())
            ->method('calculateConfigChangeSet')
            ->with($fieldConfig);

        $this->builder->addFieldConfig(
            'Test\Entity',
            'testField',
            'manyToOne',
            ['extend' => ['test' => true]]
        );
    }

    public function testAddManyToOneRelation()
    {
        $relationName = 'testRelation';
        $relationKey  = 'manyToOne|Test\SourceEntity|Test\TargetEntity|testRelation';

        $extendConfig = new Config(new EntityConfigId('extend', self::SOURCE_CLASS));

        $expectedExtendConfig = new Config(new EntityConfigId('extend', self::SOURCE_CLASS));
        $expectedExtendConfig->set(
            'relation',
            [
                $relationKey => [
                    'assign'          => false,
                    'field_id'        => new FieldConfigId('extend', self::SOURCE_CLASS, $relationName, 'manyToOne'),
                    'owner'           => true,
                    'target_entity'   => self::TARGET_CLASS,
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

        $extendConfigProvider = $this->getConfigProviderMock();
        $extendConfigProvider->expects($this->once())
            ->method('getConfig')
            ->with(self::SOURCE_CLASS)
            ->will($this->returnValue($extendConfig));
        $extendConfigProvider->expects($this->once())
            ->method('persist')
            ->with($this->identicalTo($extendConfig));

        $this->configManager->expects($this->any())
            ->method('getProvider')
            ->will(
                $this->returnValueMap(
                    [
                        ['extend', $extendConfigProvider]
                    ]
                )
            );
        $this->configManager->expects($this->once())
            ->method('calculateConfigChangeSet')
            ->with($this->identicalTo($extendConfig));

        $this->builder->addManyToOneRelation(
            self::TARGET_CLASS,
            self::SOURCE_CLASS,
            $relationName,
            $relationKey
        );

        $this->assertEquals($expectedExtendConfig, $extendConfig);
    }

    public function testAddManyToOneRelationTargetSide()
    {
        $relationName = 'testRelation';
        $relationKey  = 'manyToOne|Test\SourceEntity|Test\TargetEntity|testRelation';

        $extendConfig = new Config(new EntityConfigId('extend', self::TARGET_CLASS));

        $expectedExtendConfig = new Config(new EntityConfigId('extend', self::TARGET_CLASS));
        $expectedExtendConfig->set(
            'relation',
            [
                $relationKey => [
                    'assign'          => false,
                    'target_field_id' => new FieldConfigId('extend', self::SOURCE_CLASS, $relationName, 'manyToOne'),
                    'owner'           => false,
                    'target_entity'   => self::SOURCE_CLASS,
                    'field_id'        => false
                ]
            ]
        );

        $extendConfigProvider = $this->getConfigProviderMock();
        $extendConfigProvider->expects($this->once())
            ->method('getConfig')
            ->with(self::TARGET_CLASS)
            ->will($this->returnValue($extendConfig));
        $extendConfigProvider->expects($this->once())
            ->method('persist')
            ->with($this->identicalTo($extendConfig));

        $this->configManager->expects($this->any())
            ->method('getProvider')
            ->will(
                $this->returnValueMap(
                    [
                        ['extend', $extendConfigProvider]
                    ]
                )
            );
        $this->configManager->expects($this->once())
            ->method('calculateConfigChangeSet')
            ->with($this->identicalTo($extendConfig));

        $this->builder->addManyToOneRelationTargetSide(
            self::TARGET_CLASS,
            self::SOURCE_CLASS,
            $relationName,
            $relationKey
        );

        $this->assertEquals($expectedExtendConfig, $extendConfig);
    }

    public function testAddManyToManyRelation()
    {
        $relationName = 'testRelation';
        $relationKey  = 'manyToOne|Test\SourceEntity|Test\TargetEntity|testRelation';

        $extendConfig = new Config(new EntityConfigId('extend', self::TARGET_CLASS));

        $expectedExtendConfig = new Config(new EntityConfigId('extend', self::TARGET_CLASS));
        $expectedExtendConfig->set(
            'relation',
            [
                $relationKey => [
                    'assign'          => false,
                    'field_id'        => new FieldConfigId('extend', self::SOURCE_CLASS, $relationName, 'manyToMany'),
                    'owner'           => true,
                    'target_entity'   => self::TARGET_CLASS,
                    'target_field_id' => false,
                ]
            ]
        );
        $expectedExtendConfig->set('index', [$relationName => null]);

        $extendConfigProvider = $this->getConfigProviderMock();
        $extendConfigProvider->expects($this->once())
            ->method('getConfig')
            ->with(self::SOURCE_CLASS)
            ->will($this->returnValue($extendConfig));
        $extendConfigProvider->expects($this->once())
            ->method('persist')
            ->with($this->identicalTo($extendConfig));

        $this->configManager->expects($this->any())
            ->method('getProvider')
            ->will(
                $this->returnValueMap(
                    [
                        ['extend', $extendConfigProvider]
                    ]
                )
            );
        $this->configManager->expects($this->once())
            ->method('calculateConfigChangeSet')
            ->with($this->identicalTo($extendConfig));

        $this->builder->addManyToManyRelation(
            self::TARGET_CLASS,
            self::SOURCE_CLASS,
            $relationName,
            $relationKey
        );

        $this->assertEquals($expectedExtendConfig, $extendConfig);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    protected function getConfigProviderMock()
    {
        return $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider')
            ->disableOriginalConstructor()
            ->getMock();
    }
}
