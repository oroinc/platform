<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\Tools;

use Oro\Bundle\EntityConfigBundle\Config\Config;
use Oro\Bundle\EntityConfigBundle\Config\Id\EntityConfigId;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\EntityExtendBundle\Tools\DumperExtensions\RelationDataConfigDumperExtension;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendConfigDumper;

class RelationDataConfigDumperExtensionTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $configManager;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $extendConfigProvider;

    /** @var  RelationDataConfigDumperExtension */
    protected $extension;

    public function setUp()
    {
        $this->extendConfigProvider = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $this->configManager = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Config\ConfigManager')
            ->disableOriginalConstructor()
            ->setMethods(['getProviderBag', 'getProvider', 'getConfigChangeSet'])
            ->getMock();


        $this->configManager
            ->expects($this->once())
            ->method('getProvider')
            ->with('extend')
            ->will($this->returnValue($this->extendConfigProvider));

        $this->extension = new RelationDataConfigDumperExtension($this->configManager);
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

    /**
     *  Test create new field (relation type [1:*])
     */
    public function testCreateSelfRelationOneToMany()
    {
        $config1 = new Config(new EntityConfigId('extend', 'Test\Entity'));
        $config2 = new Config(new EntityConfigId('extend', 'TestClass'));
        $config2->set('owner', ExtendScope::OWNER_CUSTOM);

        $configs = [
            $config1, $config2
        ];

        $fieldsConfigs = [
            $this->getConfigNewField(
                [
                    'state' => ExtendScope::STATE_NEW,
                    'target_entity' => 'Oro\Bundle\UserBundle\Entity\User',
                ],
                'oneToMany'
            ),
        ];

        // get field configs
        $this->extendConfigProvider
            ->expects($this->once())
            ->method('getConfigs')
            ->with('TestClass')
            ->will($this->returnValue($fieldsConfigs));

        $selfConfig = $this->getEntityConfig(['state' => ExtendScope::STATE_ACTIVE]);

        $this->extendConfigProvider
            ->expects($this->at(1))
            ->method('getConfig')
            ->with('TestClass')
            ->will($this->returnValue($selfConfig));

        $this->extendConfigProvider
            ->expects($this->at(2))
            ->method('getConfig')
            ->with('TestClass')
            ->will($this->returnValue($selfConfig));

        $targetConfig = $this->getEntityConfig(['state' => ExtendScope::STATE_ACTIVE]);
        $this->extendConfigProvider
            ->expects($this->at(4))
            ->method('getConfig')
            ->with('Oro\Bundle\UserBundle\Entity\User')
            ->will($this->returnValue($targetConfig));

        $this->extension->preUpdate($configs);

        $this->assertEquals(
            [
                'oneToMany|TestClass|Oro\Bundle\UserBundle\Entity\User|testFieldName' => [
                    'assign'          => false,
                    'field_id'        => new FieldConfigId(
                        'extend',
                        'Oro\Bundle\UserBundle\Entity\User',
                        'testclass_testFieldName',
                        'manyToOne'
                    ),
                    'owner'           => true,
                    'target_entity'   => 'TestClass',
                    'target_field_id' => new FieldConfigId(
                        'extend',
                        'TestClass',
                        'testFieldName',
                        'oneToMany'
                    ),
                ],
            ],
            $targetConfig->get('relation')
        );

        $this->assertEquals(
            [
                'oneToMany|TestClass|Oro\Bundle\UserBundle\Entity\User|testFieldName' => [
                    'assign'          => false,
                    'field_id'        => new FieldConfigId(
                        'extend',
                        'TestClass',
                        'testFieldName',
                        'oneToMany'
                    ),
                    'owner'           => false,
                    'target_entity'   => 'Oro\Bundle\UserBundle\Entity\User',
                    'target_field_id' => new FieldConfigId(
                        'extend',
                        'Oro\Bundle\UserBundle\Entity\User',
                        'testclass_testFieldName',
                        'manyToOne'
                    ),
                ],
            ],
            $selfConfig->get('relation')
        );
    }

    /**
     *  Test create new field (relation type [*:1])
     */
    public function testCreateSelfRelationManyToOne()
    {
        $config1 = new Config(new EntityConfigId('extend', 'Test\Entity'));
        $config2 = new Config(new EntityConfigId('extend', 'TestClass'));
        $config2->set('owner', ExtendScope::OWNER_CUSTOM);

        $configs = [
            $config1, $config2
        ];

        $fieldsConfigs = [
            $this->getConfigNewField(
                [
                    'state' => ExtendScope::STATE_NEW,
                    'target_entity' => 'Oro\Bundle\UserBundle\Entity\User',
                ],
                'manyToOne'
            ),
        ];

        // get field configs
        $this->extendConfigProvider
            ->expects($this->once())
            ->method('getConfigs')
            ->with('TestClass')
            ->will($this->returnValue($fieldsConfigs));

        $selfConfig = $this->getEntityConfig(['state' => ExtendScope::STATE_ACTIVE]);

        $this->extendConfigProvider
            ->expects($this->at(1))
            ->method('getConfig')
            ->with('TestClass')
            ->will($this->returnValue($selfConfig));

        $this->extendConfigProvider
            ->expects($this->at(2))
            ->method('getConfig')
            ->with('TestClass')
            ->will($this->returnValue($selfConfig));

        $targetConfig = $this->getEntityConfig(['state' => ExtendScope::STATE_ACTIVE]);
        $this->extendConfigProvider
            ->expects($this->at(4))
            ->method('getConfig')
            ->with('Oro\Bundle\UserBundle\Entity\User')
            ->will($this->returnValue($targetConfig));

        $this->extension->preUpdate($configs);

        $this->assertEquals(
            [
                'manyToOne|TestClass|Oro\Bundle\UserBundle\Entity\User|testFieldName' => [
                    'assign'          => false,
                    'field_id'        => false,
                    'owner'           => false,
                    'target_entity'   => 'TestClass',
                    'target_field_id' => new FieldConfigId(
                        'extend',
                        'TestClass',
                        'testFieldName',
                        'manyToOne'
                    ),
                ],
            ],
            $targetConfig->get('relation')
        );

        $this->assertEquals(
            [
                'manyToOne|TestClass|Oro\Bundle\UserBundle\Entity\User|testFieldName' => [
                    'assign'          => false,
                    'field_id'        => new FieldConfigId(
                        'extend',
                        'TestClass',
                        'testFieldName',
                        'manyToOne'
                    ),
                    'owner'           => true,
                    'target_entity'   => 'Oro\Bundle\UserBundle\Entity\User',
                    'target_field_id' => false,
                ],
            ],
            $selfConfig->get('relation')
        );
    }

    /**
     * FieldConfig
     *
     * @param array $values
     * @param string $type
     * @param string $scope
     *
     * @return Config
     */
    protected function getConfigNewField($values = [], $type = 'string', $scope = 'extend')
    {
        $resultValues = [
            'owner'      => ExtendScope::OWNER_CUSTOM,
            'state'      => ExtendScope::STATE_NEW,
            'is_extend'  => true,
            'is_deleted' => false,
        ];

        if (count($values)) {
            $resultValues = array_merge($resultValues, $values);
        }

        $fieldConfigId = new FieldConfigId($scope, 'TestClass', 'testFieldName', $type);
        $config   = new Config($fieldConfigId);
        $config->setValues($resultValues);

        return $config;
    }

    /**
     * EntityConfig
     *
     * @param array $values
     * @param string $scope
     * @return Config
     */
    protected function getEntityConfig($values = [], $scope = 'extend')
    {
        $resultValues = [
            'owner'       => ExtendScope::OWNER_CUSTOM,
            'is_extend'   => true,
            'state'       => ExtendScope::STATE_NEW,
            'is_deleted'  => false,
            'upgradeable' => false,
            'relation'    => [],
            'schema'      => [],
            'index'       => []
        ];

        if (count($values)) {
            $resultValues = array_merge($resultValues, $values);
        }

        $entityConfigId = new EntityConfigId($scope, 'TestClass');
        $entityConfig   = new Config($entityConfigId);
        $entityConfig->setValues($resultValues);

        return $entityConfig;
    }
}
