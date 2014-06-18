<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\Tools;

use Oro\Bundle\EntityConfigBundle\Config\Config;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Config\Id\EntityConfigId;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityExtendBundle\Tools\AssociationBuildHelper;
use Oro\Bundle\EntityExtendBundle\Tests\Util\ReflectionUtil;

class AssociationBuildHelperTest extends \PHPUnit_Framework_TestCase
{
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

    /**
     * Test createManyToManyRelation call methods with correct params
     */
    public function testCreateManyToManyRelation()
    {
        $sourceClass = 'Test\Activity';
        $targetClass = 'Test\Something\More';

        /** @var \PHPUnit_Framework_MockObject_MockObject|AssociationBuildHelper $helper */
        $helper = $this->getMock(
            'Oro\Bundle\EntityExtendBundle\Tools\AssociationBuildHelper',
            ['getFieldNames', 'createFieldModel', 'addManyToManyRelation', 'getScopeConfigs'],
            [$this->configManagerMock]
        );

        $helper->expects($this->once())
            ->method('getScopeConfigs')
            ->will($this->returnValue(new Config(new EntityConfigId('entity', $targetClass))));

        $fieldNames = ['id', 'name'];
        $helper->expects($this->once())
            ->method('getFieldNames')
            ->with($targetClass)
            ->will($this->returnValue($fieldNames));

        $helper->expects($this->once())
            ->method('createFieldModel')
            ->with(
                $sourceClass,
                'more_929b3e16',
                'manyToMany',
                [
                    'extend' => [
                        'owner'           => 'System',
                        'state'           => 'New',
                        'extend'          => true,
                        'is_inverse'      => false,
                        'relation_key'    => 'manyToMany|Test\Activity|Test\Something\More|more_929b3e16',
                        'target_entity'   => $targetClass,
                        'target_grid'     => ['name'],
                        'target_title'    => ['id'],
                        'target_detailed' => ['name'],
                    ],
                    'entity' => [
                        'label'       => 'test.something.more.more_929b3e16.label',
                        'description' => 'test.something.more.more_929b3e16.description',
                    ],
                    'view'   => [
                        'is_displayable' => true
                    ],
                    'form'   => [
                        'is_enabled' => true
                    ]
                ]
            );

        $helper->expects($this->once())
            ->method('addManyToManyRelation')
            ->with(
                $targetClass,
                $sourceClass,
                'more_929b3e16',
                'manyToMany|Test\Activity|Test\Something\More|more_929b3e16',
                true
            );

        $helper->createManyToManyAssociation($sourceClass, $targetClass);
    }

    /**
     * Test get scope configs
     */
    public function testGetScopeConfigs()
    {
        $scopeConfigs = [];

        // test get configs for all entities
        $provider = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $provider->expects($this->once())
            ->method('getConfigs')
            ->will($this->returnValue($scopeConfigs));

        $this->configManagerMock->expects($this->once())
            ->method('getProvider')
            ->with('test')
            ->will($this->returnValue($provider));

        $configs = $this->helper->getScopeConfigs('test');
        $this->assertSame($scopeConfigs, $configs, 'Configs returned from provider');
    }

    /**
     * Test getter for entity config
     */
    public function testGetScopeConfig()
    {
        // test get specific entity config
        $provider = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $provider->expects($this->once())
            ->method('getConfig')
            ->with('Test\Entity')
            ->will($this->returnValue(new Config(new EntityConfigId('entity'))));

        $this->configManagerMock->expects($this->once())
            ->method('getProvider')
            ->with('test')
            ->will($this->returnValue($provider));

        $config = $this->helper->getScopeConfigs('test', 'Test\Entity');
        $this->assertInstanceOf('Oro\Bundle\EntityConfigBundle\Config\Config', $config, 'method returned Config object');
    }

    public function testCreateManyToOneRelation()
    {
        $sourceClass = 'Test\Activity';
        $targetClass = 'Test\Something\More';

        /** @var \PHPUnit_Framework_MockObject_MockObject|AssociationBuildHelper $helper */
        $helper = $this->getMock(
            'Oro\Bundle\EntityExtendBundle\Tools\AssociationBuildHelper',
            [
                'getPrimaryKeyColumnNames',
                'createFieldModel',
                'addManyToOneRelation',
                'addManyToOneRelationTargetSide',
                'getScopeConfigs'
            ],
            [$this->configManagerMock]
        );

        $helper->expects($this->once())
            ->method('getScopeConfigs')
            ->will($this->returnValue(new Config(new EntityConfigId('entity', $targetClass))));

        $fieldNames = ['id', 'name'];
        $helper->expects($this->once())
            ->method('getPrimaryKeyColumnNames')
            ->with($targetClass)
            ->will($this->returnValue($fieldNames));

        $helper->expects($this->once())
            ->method('createFieldModel')
            ->with(
                $sourceClass,
                'more_929b3e16',
                'manyToOne',
                [
                    'extend' => [
                        'owner'         => 'System',
                        'state'         => 'New',
                        'extend'        => true,
                        'target_entity' => $targetClass,
                        'target_field'  => 'id',
                        'relation_key'  => 'manyToOne|Test\Activity|Test\Something\More|more_929b3e16',
                    ],
                    'entity' => [
                        'label'       => 'test.something.more.more_929b3e16.label',
                        'description' => 'test.something.more.more_929b3e16.description',
                    ],
                    'view'   => [
                        'is_displayable' => false
                    ],
                    'form'   => [
                        'is_enabled' => false
                    ]
                ]
            );

        $helper->expects($this->once())
        ->method('addManyToOneRelation')
        ->with(
            $targetClass,
            $sourceClass,
            'more_929b3e16',
            'manyToOne|Test\Activity|Test\Something\More|more_929b3e16'
        );

        $helper->expects($this->once())
            ->method('addManyToOneRelationTargetSide')
            ->with(
                $targetClass,
                $sourceClass,
                'more_929b3e16',
                'manyToOne|Test\Activity|Test\Something\More|more_929b3e16'
            );

        $helper->createManyToOneAssociation($sourceClass, $targetClass);
    }

    /**
     * Test getPrimaryKeyColumnNames returned column names
     */
    public function testPrimaryKeyColumnNames()
    {
        $entityClass = 'Test\Entity';

        $emMock = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $metadataMock = $this->getMockBuilder('Doctrine\ORM\Mapping\ClassMetadata')
            ->disableOriginalConstructor()
            ->getMock();

        $emMock->expects($this->once())
            ->method('getClassMetadata')
            ->with($entityClass)
            ->will($this->returnValue($metadataMock));

        $metadataMock->expects($this->once())
            ->method('getIdentifierColumnNames')
            ->will($this->returnValue(['id', 'name']));

        $this->configManagerMock->expects($this->once())
            ->method('getEntityManager')
            ->will($this->returnValue($emMock));

        $columnNames = $this->helper->getPrimaryKeyColumnNames($entityClass);
        $this->assertCount(2, $columnNames);
        $this->assertSame(['id', 'name'], $columnNames);
    }

    /**
     * Test getPrimaryKeyColumnNames returned column names due to exception
     */
    public function testPrimaryKeyColumnNamesException()
    {
        $this->configManagerMock->expects($this->once())
            ->method('getEntityManager')
            ->will($this->throwException(new \ReflectionException('test')));

        $columnNames = $this->helper->getPrimaryKeyColumnNames('Test');
        $this->assertCount(1, $columnNames);
        $this->assertSame(['id'], $columnNames);
    }

    /**
     * Test field names getter
     */
    public function testGetFieldNames()
    {
        $emMock = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $metadataMock = $this->getMockBuilder('Doctrine\ORM\Mapping\ClassMetadata')
            ->disableOriginalConstructor()
            ->getMock();

        $emMock->expects($this->once())
            ->method('getClassMetadata')
            ->with('Test')
            ->will($this->returnValue($metadataMock));

        $metadataMock->expects($this->once())
            ->method('getFieldNames')
            ->will($this->returnValue(['id', 'name']));

        $this->configManagerMock->expects($this->once())
            ->method('getEntityManager')
            ->will($this->returnValue($emMock));

        $fieldNames = $this->helper->getFieldNames('Test');
        $this->assertSame(['id', 'name'], $fieldNames);
    }

    /**
     * Test update field config call methods with correct params
     */
    public function testUpdateFieldConfigs()
    {
        $provider = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $this->configManagerMock->expects($this->once())
            ->method('getProvider')
            ->with('extend')
            ->will($this->returnValue($provider));

        $fieldConfig = $this->getMock('Oro\Bundle\EntityConfigBundle\Config\ConfigInterface');
        $fieldConfig->expects($this->once())
            ->method('set')
            ->with('test', true);

        $provider->expects($this->once())
            ->method('getConfig')
            ->with('Test\Entity', 'testField')
            ->will($this->returnValue($fieldConfig));

        $this->configManagerMock->expects($this->once())
            ->method('persist')
            ->with($fieldConfig);

        $this->configManagerMock->expects($this->once())
            ->method('calculateConfigChangeSet')
            ->with($fieldConfig);

        ReflectionUtil::callProtectedMethod(
            $this->helper,
            'updateFieldConfigs',
            ['Test\Entity', 'testField', ['extend' => ['test' => true]]]
        );
    }

    /**
     * Test createFieldModel method call configManager and updateFieldConfigs
     */
    public function testCreateField()
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|AssociationBuildHelper $helper */
        $helper = $this->getMock(
            'Oro\Bundle\EntityExtendBundle\Tools\AssociationBuildHelper',
            [
                'updateFieldConfigs',
            ],
            [$this->configManagerMock]
        );

        $this->configManagerMock->expects($this->once())
            ->method('createConfigFieldModel')
            ->with('Test\Entity', 'entity', 'manyToOne');

        $helper->expects($this->once())
            ->method('updateFieldConfigs')
            ->with('Test\Entity', 'entity', []);

        ReflectionUtil::callProtectedMethod(
            $helper,
            'createFieldModel',
            ['Test\Entity', 'entity', 'manyToOne', []]
        );
    }

    /**
     * Test method call config provider with correct params
     */
    public function testAddManyToOneRelation()
    {
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

        $extendConfigProvider = $this->configureExtendConfigProvider($sourceEntityName, $extendConfig);

        $extendConfigProvider->expects($this->once())
            ->method('persist')
            ->with($this->identicalTo($extendConfig));

        $this->configManagerMock->expects($this->once())
            ->method('calculateConfigChangeSet')
            ->with($this->identicalTo($extendConfig));

        ReflectionUtil::callProtectedMethod(
            $this->helper,
            'addManyToOneRelation',
            [$targetEntityName, $sourceEntityName, $relationName, $relationKey]
        );

        $this->assertEquals($expectedExtendConfig, $extendConfig);
    }

    /**
     * Test method correctly configure manyToOne relation on target side
     */
    public function testAddManyToOneRelationTargetSide()
    {
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

        $extendConfigProvider = $this->configureExtendConfigProvider($targetEntityName, $extendConfig);

        $extendConfigProvider->expects($this->once())
            ->method('persist')
            ->with($this->identicalTo($extendConfig));

        $this->configManagerMock->expects($this->once())
            ->method('calculateConfigChangeSet')
            ->with($this->identicalTo($extendConfig));

        ReflectionUtil::callProtectedMethod(
            $this->helper,
            'addManyToOneRelationTargetSide',
            [$targetEntityName, $sourceEntityName, $relationName, $relationKey]
        );

        $this->assertEquals($expectedExtendConfig, $extendConfig);
    }

    /**
     * Test method correctly configure manyToMany bidirectional relation
     *
     * @param   bool $isUnidirectional
     * @dataProvider manyToManyDataProvider
     */
    public function testAddManyToManyRelationBidirectional($isUnidirectional)
    {
        $targetEntityName = 'Test\TargetEntity';
        $sourceEntityName = 'Test\SourceEntity';
        $relationName     = 'entity';
        $relationKey      = 'manyToOne|Test\SourceEntity|Test\TargetEntity|entity';

        $extendConfig = new Config(new EntityConfigId('extend', $targetEntityName));

        $targetFieldId = null;
        if (!$isUnidirectional) {
            $targetFieldId = new FieldConfigId(
                'extend',
                $targetEntityName,
                'source_entity_' . $relationName,
                'manyToMany'
            );
        }

        $expectedExtendConfig = new Config(new EntityConfigId('extend', $targetEntityName));
        $expectedExtendConfig->set(
            'relation',
            [
                $relationKey => [
                    'assign'          => false,
                    'field_id'        => new FieldConfigId('extend', $sourceEntityName, $relationName, 'manyToMany'),
                    'owner'           => true,
                    'target_entity'   => $targetEntityName,
                    'target_field_id' => $targetFieldId,
                ]
            ]
        );
        $expectedExtendConfig->set('index', [$relationName => null]);

        $extendConfigProvider = $this->configureExtendConfigProvider($sourceEntityName, $extendConfig);

        $extendConfigProvider->expects($this->once())
            ->method('persist')
            ->with($this->identicalTo($extendConfig));

        $this->configManagerMock->expects($this->once())
            ->method('calculateConfigChangeSet')
            ->with($this->identicalTo($extendConfig));

        ReflectionUtil::callProtectedMethod(
            $this->helper,
            'addManyToManyRelation',
            [$targetEntityName, $sourceEntityName, $relationName, $relationKey, $isUnidirectional]
        );

        $this->assertEquals($expectedExtendConfig, $extendConfig);
    }

    /**
     * Uni- and bi- directional flags
     *
     * @return array
     */
    public function manyToManyDataProvider()
    {
        return [
            'unidirectional' => [true],
            'bidirectional'  => [false],
        ];
    }

    /**
     * @param string $sourceEntityName
     * @param Config $extendConfig
     *
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    protected function configureExtendConfigProvider($sourceEntityName, $extendConfig)
    {
        $extendConfigProvider = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $this->configManagerMock->expects($this->once())
            ->method('getProvider')
            ->with('extend')
            ->will($this->returnValue($extendConfigProvider));

        $extendConfigProvider->expects($this->once())
            ->method('getConfig')
            ->with($sourceEntityName)
            ->will($this->returnValue($extendConfig));

        return $extendConfigProvider;
    }
}
