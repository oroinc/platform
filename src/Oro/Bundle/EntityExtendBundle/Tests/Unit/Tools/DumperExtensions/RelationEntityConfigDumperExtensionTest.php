<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\Tools\DumperExtensions;

use Oro\Bundle\EntityConfigBundle\Config\Config;
use Oro\Bundle\EntityConfigBundle\Config\Id\EntityConfigId;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\EntityExtendBundle\Extend\FieldTypeHelper;
use Oro\Bundle\EntityExtendBundle\Tools\DumperExtensions\RelationEntityConfigDumperExtension;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendConfigDumper;

class RelationEntityConfigDumperExtensionTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $configManager;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $extendConfigProvider;

    /** @var  RelationEntityConfigDumperExtension */
    protected $extension;

    /** @var FieldTypeHelper */
    protected $fieldTypeHelper;

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

        $this->extension = new RelationEntityConfigDumperExtension(
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

    /**
     * Test that nothing changed and only custom fields processed
     */
    public function testCreateRelationTypeOwnEntity()
    {
        $config = new Config(new EntityConfigId('extend', 'TestClass'));
        $config->set('is_extend', true);

        $fieldsConfigs = [
            $this->getConfigNewField(
                [
                    'relation_key' => 'manyToMany|TestClass|Oro\Bundle\UserBundle\Entity\User|testFieldName'
                ],
                'oneToMany'
            )
        ];

        $relation   = [
            'manyToMany|TestClass|Oro\Bundle\UserBundle\Entity\User|testFieldName' => [
                'assign'        => true,
                'owner'         => true,
                'target_entity' => 'Oro\Bundle\UserBundle\Entity\User',
                'field_id'      => new FieldConfigId(
                    'extend',
                    'TestClass',
                    'testFieldName',
                    'oneToMany'
                )
            ]
        ];
        $selfConfig = $this->getEntityConfig(
            [
                'state'    => ExtendScope::STATE_ACTIVE,
                'relation' => $relation,
            ]
        );

        $this->extendConfigProvider->expects($this->any())
            ->method('getConfigs')
            ->will(
                $this->returnValueMap(
                    [
                        [null, false, [$config]],
                        ['TestClass', false, $fieldsConfigs],
                    ]
                )
            );
        $this->extendConfigProvider->expects($this->once())
            ->method('getConfig')
            ->with('TestClass')
            ->will($this->returnValue($selfConfig));

        $this->extension->preUpdate();

        // assert nothing changed
        $this->assertEquals($relation, $selfConfig->get('relation'));
    }

    /**
     *  Test create new field (relation type [1:*])
     */
    public function testCreateSelfRelationOneToMany()
    {
        $config = new Config(new EntityConfigId('extend', 'TestClass'));
        $config->set('is_extend', true);

        $fieldsConfigs = [
            $this->getConfigNewField(
                [
                    'state'         => ExtendScope::STATE_NEW,
                    'target_entity' => 'Oro\Bundle\UserBundle\Entity\User',
                ],
                'oneToMany'
            ),
        ];

        $selfConfig   = $this->getEntityConfig(['state' => ExtendScope::STATE_ACTIVE]);
        $targetConfig = $this->getEntityConfig(['state' => ExtendScope::STATE_ACTIVE]);

        $this->extendConfigProvider->expects($this->any())
            ->method('getConfigs')
            ->will(
                $this->returnValueMap(
                    [
                        [null, false, [$config]],
                        ['TestClass', false, $fieldsConfigs],
                    ]
                )
            );
        $this->extendConfigProvider->expects($this->any())
            ->method('getConfig')
            ->will(
                $this->returnValueMap(
                    [
                        ['TestClass', null, $selfConfig],
                        ['Oro\Bundle\UserBundle\Entity\User', null, $targetConfig],
                    ]
                )
            );

        $this->extension->preUpdate();

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
        $config = new Config(new EntityConfigId('extend', 'TestClass'));
        $config->set('is_extend', true);

        $fieldsConfigs = [
            $this->getConfigNewField(
                [
                    'state'         => ExtendScope::STATE_NEW,
                    'target_entity' => 'Oro\Bundle\UserBundle\Entity\User',
                ],
                'manyToOne'
            ),
        ];

        $selfConfig   = $this->getEntityConfig(['state' => ExtendScope::STATE_ACTIVE]);
        $targetConfig = $this->getEntityConfig(['state' => ExtendScope::STATE_ACTIVE]);

        $this->extendConfigProvider->expects($this->any())
            ->method('getConfigs')
            ->will(
                $this->returnValueMap(
                    [
                        [null, false, [$config]],
                        ['TestClass', false, $fieldsConfigs],
                    ]
                )
            );
        $this->extendConfigProvider->expects($this->any())
            ->method('getConfig')
            ->will(
                $this->returnValueMap(
                    [
                        ['TestClass', null, $selfConfig],
                        ['Oro\Bundle\UserBundle\Entity\User', null, $targetConfig],
                    ]
                )
            );

        $this->extension->preUpdate();

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
     *  Test create new field (relation type [*:*])
     */
    public function testCreateSelfRelationManyToMany()
    {
        $config = new Config(new EntityConfigId('extend', 'TestClass'));
        $config->set('is_extend', true);

        $fieldsConfigs = [
            $this->getConfigNewField(
                [
                    'target_entity' => 'Oro\Bundle\UserBundle\Entity\User',
                ],
                'manyToMany'
            ),
        ];

        $selfConfig   = $this->getEntityConfig(['state' => ExtendScope::STATE_ACTIVE]);
        $targetConfig = $this->getEntityConfig(['state' => ExtendScope::STATE_ACTIVE]);

        $this->extendConfigProvider->expects($this->any())
            ->method('getConfigs')
            ->will(
                $this->returnValueMap(
                    [
                        [null, false, [$config]],
                        ['TestClass', false, $fieldsConfigs],
                    ]
                )
            );
        $this->extendConfigProvider->expects($this->any())
            ->method('getConfig')
            ->will(
                $this->returnValueMap(
                    [
                        ['TestClass', null, $selfConfig],
                        ['Oro\Bundle\UserBundle\Entity\User', null, $targetConfig],
                    ]
                )
            );

        $this->extension->preUpdate();

        $this->assertEquals(
            [
                'manyToMany|TestClass|Oro\Bundle\UserBundle\Entity\User|testFieldName' => [
                    'assign'          => false,
                    'owner'           => false,
                    'field_id'        => new FieldConfigId(
                        'extend',
                        'Oro\Bundle\UserBundle\Entity\User',
                        'testclass_testFieldName',
                        'manyToMany'
                    ),
                    'target_entity'   => 'TestClass',
                    'target_field_id' => new FieldConfigId(
                        'extend',
                        'TestClass',
                        'testFieldName',
                        'manyToMany'
                    ),
                ],
            ],
            $targetConfig->get('relation')
        );

        $this->assertEquals(
            [
                'manyToMany|TestClass|Oro\Bundle\UserBundle\Entity\User|testFieldName' => [
                    'assign'          => false,
                    'field_id'        => new FieldConfigId(
                        'extend',
                        'TestClass',
                        'testFieldName',
                        'manyToMany'
                    ),
                    'owner'           => true,
                    'target_entity'   => 'Oro\Bundle\UserBundle\Entity\User',
                    'target_field_id' => new FieldConfigId(
                        'extend',
                        'Oro\Bundle\UserBundle\Entity\User',
                        'testclass_testFieldName',
                        'manyToMany'
                    ),
                ],
            ],
            $selfConfig->get('relation')
        );
    }

    /**
     *  Test create new field (relation type [*:*])
     */
    public function testCreateTargetRelationManyToMany()
    {
        $config = new Config(new EntityConfigId('extend', 'TestClass'));
        $config->set('is_extend', true);

        $fieldsConfigs = [
            $this->getConfigNewField(
                [
                    'target_entity'   => 'Oro\Bundle\UserBundle\Entity\User',
                    'target_title'    => ['username'],
                    'target_grid'     => ['username'],
                    'target_detailed' => ['username'],
                    'relation_key'    => 'manyToMany|TestClass|Oro\Bundle\UserBundle\Entity\User|testFieldName',
                ],
                'manyToMany'
            ),
        ];

        $relation = [
            'manyToMany|TestClass|Oro\Bundle\UserBundle\Entity\User|testFieldName' => [
                'assign'        => true,
                'owner'         => true,
                'target_entity' => 'Oro\Bundle\UserBundle\Entity\User',
                'field_id'      => new FieldConfigId(
                    'extend',
                    'TestClass',
                    'testFieldName',
                    'manyToMany'
                )
            ]
        ];

        $selfConfig   = $this->getEntityConfig(
            [
                'state'    => ExtendScope::STATE_ACTIVE,
                'relation' => [],
            ]
        );
        $targetConfig = $this->getEntityConfig(
            [
                'state'    => ExtendScope::STATE_ACTIVE,
                'relation' => $relation,
            ],
            'extend',
            'Oro\Bundle\UserBundle\Entity\User'
        );

        $this->extendConfigProvider->expects($this->any())
            ->method('getConfigs')
            ->will(
                $this->returnValueMap(
                    [
                        [null, false, [$config]],
                        ['TestClass', false, $fieldsConfigs],
                    ]
                )
            );
        $this->extendConfigProvider->expects($this->any())
            ->method('getConfig')
            ->will(
                $this->returnValueMap(
                    [
                        ['TestClass', null, $selfConfig],
                        ['Oro\Bundle\UserBundle\Entity\User', null, $targetConfig],
                    ]
                )
            );

        $this->extension->preUpdate();

        // setup expected relation array
        $relationKey = 'manyToMany|TestClass|Oro\Bundle\UserBundle\Entity\User|testFieldName';
        $relation[$relationKey]['target_field_id'] = $relation[$relationKey]['field_id'];

        $this->assertEquals(
            $relation,
            $targetConfig->get('relation')
        );

        $this->assertEquals($relation, $targetConfig->get('relation'));
    }

    /**
     * FieldConfig
     *
     * @param array  $values
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
            'is_deleted' => false,
        ];

        if (count($values)) {
            $resultValues = array_merge($resultValues, $values);
        }

        $fieldConfigId = new FieldConfigId($scope, 'TestClass', 'testFieldName', $type);
        $config        = new Config($fieldConfigId);
        $config->setValues($resultValues);

        return $config;
    }

    /**
     * EntityConfig
     *
     * @param array  $values
     * @param string $scope
     * @param string $className
     *
     * @return Config
     */
    protected function getEntityConfig($values = [], $scope = 'extend', $className = 'TestClass')
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

        $entityConfigId = new EntityConfigId($scope, $className);
        $entityConfig   = new Config($entityConfigId);
        $entityConfig->setValues($resultValues);

        return $entityConfig;
    }
}
