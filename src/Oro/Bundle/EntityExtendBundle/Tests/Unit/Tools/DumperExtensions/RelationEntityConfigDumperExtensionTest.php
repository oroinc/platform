<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\Tools\DumperExtensions;

use Oro\Bundle\EntityConfigBundle\Config\Config;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityConfigBundle\Tests\Unit\ConfigProviderMock;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\EntityExtendBundle\Extend\FieldTypeHelper;
use Oro\Bundle\EntityExtendBundle\Tools\DumperExtensions\RelationEntityConfigDumperExtension;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendConfigDumper;

class RelationEntityConfigDumperExtensionTest extends \PHPUnit_Framework_TestCase
{
    /** @var ConfigProviderMock */
    protected $extendConfigProvider;

    /** @var  RelationEntityConfigDumperExtension */
    protected $extension;

    /** @var FieldTypeHelper */
    protected $fieldTypeHelper;

    public function setUp()
    {
        $configManager = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Config\ConfigManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->extendConfigProvider = new ConfigProviderMock($configManager, 'extend');

        $configManager
            ->expects($this->once())
            ->method('getProvider')
            ->with('extend')
            ->will($this->returnValue($this->extendConfigProvider));

        $this->extension = new RelationEntityConfigDumperExtension(
            $configManager,
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
        $relation   = [
            'manyToMany|Test\SourceEntity|Test\TargetEntity|testField' => [
                'owner'         => true,
                'target_entity' => 'Test\TargetEntity',
                'field_id'      => new FieldConfigId(
                    'extend',
                    'Test\SourceEntity',
                    'testField',
                    'oneToMany'
                )
            ]
        ];
        $selfConfig = $this->addEntityConfig(
            [
                'is_extend' => true,
                'state'     => ExtendScope::STATE_ACTIVE,
                'relation'  => $relation,
            ]
        );
        $this->addConfigNewField(
            [
                'relation_key' => 'manyToMany|Test\SourceEntity|Test\TargetEntity|testField'
            ],
            'oneToMany'
        );

        $this->extension->preUpdate();

        // assert nothing changed
        $this->assertEquals($relation, $selfConfig->get('relation'));
    }

    /**
     *  Test create new field (relation type [1:*])
     */
    public function testCreateSelfRelationOneToMany()
    {
        $selfConfig = $this->addEntityConfig(['is_extend' => true, 'state' => ExtendScope::STATE_ACTIVE]);
        $this->addConfigNewField(
            [
                'state'         => ExtendScope::STATE_NEW,
                'target_entity' => 'Test\TargetEntity',
            ],
            'oneToMany'
        );
        $targetConfig = $this->addEntityConfig(['state' => ExtendScope::STATE_ACTIVE], 'Test\TargetEntity');

        $this->extension->preUpdate();

        $this->assertEquals(
            [
                'oneToMany|Test\SourceEntity|Test\TargetEntity|testField' => [
                    'field_id'        => new FieldConfigId(
                        'extend',
                        'Test\TargetEntity',
                        'sourceentity_testField',
                        'manyToOne'
                    ),
                    'owner'           => true,
                    'target_entity'   => 'Test\SourceEntity',
                    'target_field_id' => new FieldConfigId(
                        'extend',
                        'Test\SourceEntity',
                        'testField',
                        'oneToMany'
                    ),
                ],
            ],
            $targetConfig->get('relation')
        );

        $this->assertEquals(
            [
                'oneToMany|Test\SourceEntity|Test\TargetEntity|testField' => [
                    'field_id'        => new FieldConfigId(
                        'extend',
                        'Test\SourceEntity',
                        'testField',
                        'oneToMany'
                    ),
                    'owner'           => false,
                    'target_entity'   => 'Test\TargetEntity',
                    'target_field_id' => new FieldConfigId(
                        'extend',
                        'Test\TargetEntity',
                        'sourceentity_testField',
                        'manyToOne'
                    ),
                ],
            ],
            $selfConfig->get('relation')
        );
    }

    /**
     *  Test create new field (relation type [1:*] + cascade option)
     */
    public function testCreateSelfRelationOneToManyWithCascade()
    {
        $selfConfig = $this->addEntityConfig(['is_extend' => true, 'state' => ExtendScope::STATE_ACTIVE]);
        $this->addConfigNewField(
            [
                'state'         => ExtendScope::STATE_NEW,
                'target_entity' => 'Test\TargetEntity',
                'cascade'       => ['persist', 'remove']
            ],
            'oneToMany'
        );
        $targetConfig = $this->addEntityConfig(['state' => ExtendScope::STATE_ACTIVE], 'Test\TargetEntity');

        $this->extension->preUpdate();

        $this->assertEquals(
            [
                'oneToMany|Test\SourceEntity|Test\TargetEntity|testField' => [
                    'field_id'        => new FieldConfigId(
                        'extend',
                        'Test\TargetEntity',
                        'sourceentity_testField',
                        'manyToOne'
                    ),
                    'owner'           => true,
                    'target_entity'   => 'Test\SourceEntity',
                    'target_field_id' => new FieldConfigId(
                        'extend',
                        'Test\SourceEntity',
                        'testField',
                        'oneToMany'
                    )
                ],
            ],
            $targetConfig->get('relation')
        );

        $this->assertEquals(
            [
                'oneToMany|Test\SourceEntity|Test\TargetEntity|testField' => [
                    'field_id'        => new FieldConfigId(
                        'extend',
                        'Test\SourceEntity',
                        'testField',
                        'oneToMany'
                    ),
                    'owner'           => false,
                    'target_entity'   => 'Test\TargetEntity',
                    'target_field_id' => new FieldConfigId(
                        'extend',
                        'Test\TargetEntity',
                        'sourceentity_testField',
                        'manyToOne'
                    ),
                    'cascade'         => ['persist', 'remove']
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
        $selfConfig = $this->addEntityConfig(['is_extend' => true, 'state' => ExtendScope::STATE_ACTIVE]);
        $this->addConfigNewField(
            [
                'state'         => ExtendScope::STATE_NEW,
                'target_entity' => 'Test\TargetEntity',
            ],
            'manyToOne'
        );
        $targetConfig = $this->addEntityConfig(['state' => ExtendScope::STATE_ACTIVE], 'Test\TargetEntity');

        $this->extension->preUpdate();

        $this->assertEquals(
            [
                'manyToOne|Test\SourceEntity|Test\TargetEntity|testField' => [
                    'field_id'        => false,
                    'owner'           => false,
                    'target_entity'   => 'Test\SourceEntity',
                    'target_field_id' => new FieldConfigId(
                        'extend',
                        'Test\SourceEntity',
                        'testField',
                        'manyToOne'
                    ),
                ],
            ],
            $targetConfig->get('relation')
        );

        $this->assertEquals(
            [
                'manyToOne|Test\SourceEntity|Test\TargetEntity|testField' => [
                    'field_id'        => new FieldConfigId(
                        'extend',
                        'Test\SourceEntity',
                        'testField',
                        'manyToOne'
                    ),
                    'owner'           => true,
                    'target_entity'   => 'Test\TargetEntity',
                    'target_field_id' => false,
                ],
            ],
            $selfConfig->get('relation')
        );
    }

    /**
     *  Test create new field (relation type [*:1] + cascade option)
     */
    public function testCreateSelfRelationManyToOneWithCascade()
    {
        $selfConfig = $this->addEntityConfig(['is_extend' => true, 'state' => ExtendScope::STATE_ACTIVE]);
        $this->addConfigNewField(
            [
                'state'         => ExtendScope::STATE_NEW,
                'target_entity' => 'Test\TargetEntity',
                'cascade'       => ['persist', 'remove']
            ],
            'manyToOne'
        );
        $targetConfig = $this->addEntityConfig(['state' => ExtendScope::STATE_ACTIVE], 'Test\TargetEntity');

        $this->extension->preUpdate();

        $this->assertEquals(
            [
                'manyToOne|Test\SourceEntity|Test\TargetEntity|testField' => [
                    'field_id'        => false,
                    'owner'           => false,
                    'target_entity'   => 'Test\SourceEntity',
                    'target_field_id' => new FieldConfigId(
                        'extend',
                        'Test\SourceEntity',
                        'testField',
                        'manyToOne'
                    ),
                ],
            ],
            $targetConfig->get('relation')
        );

        $this->assertEquals(
            [
                'manyToOne|Test\SourceEntity|Test\TargetEntity|testField' => [
                    'field_id'        => new FieldConfigId(
                        'extend',
                        'Test\SourceEntity',
                        'testField',
                        'manyToOne'
                    ),
                    'owner'           => true,
                    'target_entity'   => 'Test\TargetEntity',
                    'target_field_id' => false,
                    'cascade'         => ['persist', 'remove']
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
        $selfConfig = $this->addEntityConfig(['is_extend' => true, 'state' => ExtendScope::STATE_ACTIVE]);
        $this->addConfigNewField(
            [
                'target_entity' => 'Test\TargetEntity',
            ],
            'manyToMany'
        );
        $targetConfig = $this->addEntityConfig(['state' => ExtendScope::STATE_ACTIVE], 'Test\TargetEntity');

        $this->extension->preUpdate();

        $this->assertEquals(
            [
                'manyToMany|Test\SourceEntity|Test\TargetEntity|testField' => [
                    'owner'           => false,
                    'field_id'        => new FieldConfigId(
                        'extend',
                        'Test\TargetEntity',
                        'sourceentity_testField',
                        'manyToMany'
                    ),
                    'target_entity'   => 'Test\SourceEntity',
                    'target_field_id' => new FieldConfigId(
                        'extend',
                        'Test\SourceEntity',
                        'testField',
                        'manyToMany'
                    ),
                ],
            ],
            $targetConfig->get('relation')
        );

        $this->assertEquals(
            [
                'manyToMany|Test\SourceEntity|Test\TargetEntity|testField' => [
                    'field_id'        => new FieldConfigId(
                        'extend',
                        'Test\SourceEntity',
                        'testField',
                        'manyToMany'
                    ),
                    'owner'           => true,
                    'target_entity'   => 'Test\TargetEntity',
                    'target_field_id' => new FieldConfigId(
                        'extend',
                        'Test\TargetEntity',
                        'sourceentity_testField',
                        'manyToMany'
                    ),
                ],
            ],
            $selfConfig->get('relation')
        );
    }

    /**
     *  Test create new field (relation type [*:*] + cascade option)
     */
    public function testCreateSelfRelationManyToManyWithCascade()
    {
        $selfConfig = $this->addEntityConfig(['is_extend' => true, 'state' => ExtendScope::STATE_ACTIVE]);
        $this->addConfigNewField(
            [
                'target_entity' => 'Test\TargetEntity',
                'cascade'       => ['persist', 'remove']
            ],
            'manyToMany'
        );
        $targetConfig = $this->addEntityConfig(['state' => ExtendScope::STATE_ACTIVE], 'Test\TargetEntity');

        $this->extension->preUpdate();

        $this->assertEquals(
            [
                'manyToMany|Test\SourceEntity|Test\TargetEntity|testField' => [
                    'owner'           => false,
                    'field_id'        => new FieldConfigId(
                        'extend',
                        'Test\TargetEntity',
                        'sourceentity_testField',
                        'manyToMany'
                    ),
                    'target_entity'   => 'Test\SourceEntity',
                    'target_field_id' => new FieldConfigId(
                        'extend',
                        'Test\SourceEntity',
                        'testField',
                        'manyToMany'
                    ),
                ],
            ],
            $targetConfig->get('relation')
        );

        $this->assertEquals(
            [
                'manyToMany|Test\SourceEntity|Test\TargetEntity|testField' => [
                    'field_id'        => new FieldConfigId(
                        'extend',
                        'Test\SourceEntity',
                        'testField',
                        'manyToMany'
                    ),
                    'owner'           => true,
                    'target_entity'   => 'Test\TargetEntity',
                    'target_field_id' => new FieldConfigId(
                        'extend',
                        'Test\TargetEntity',
                        'sourceentity_testField',
                        'manyToMany'
                    ),
                    'cascade'         => ['persist', 'remove']
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
        $this->addEntityConfig(
            [
                'is_extend' => true,
                'state'     => ExtendScope::STATE_ACTIVE,
                'relation'  => [
                    'manyToMany|Test\SourceEntity|Test\TargetEntity|testField' => [
                        'owner'         => false,
                        'target_entity' => 'Test\TargetEntity',
                        'field_id'      => new FieldConfigId(
                            'extend',
                            'Test\SourceEntity',
                            'testField',
                            'manyToMany'
                        )
                    ]
                ],
            ]
        );
        $this->addConfigNewField(
            [
                'target_entity'   => 'Test\TargetEntity',
                'target_title'    => ['username'],
                'target_grid'     => ['username'],
                'target_detailed' => ['username'],
                'relation_key'    => 'manyToMany|Test\SourceEntity|Test\TargetEntity|testField',
            ],
            'manyToMany'
        );
        $targetConfig = $this->addEntityConfig(
            [
                'state'    => ExtendScope::STATE_ACTIVE,
                'relation' => [
                    'manyToMany|Test\SourceEntity|Test\TargetEntity|testField' => [
                        'owner'         => true,
                        'target_entity' => 'Test\TargetEntity',
                        'field_id'      => new FieldConfigId(
                            'extend',
                            'Test\SourceEntity',
                            'testField',
                            'manyToMany'
                        )
                    ]
                ],
            ],
            'Test\TargetEntity'
        );

        $this->extension->preUpdate();

        $this->assertEquals(
            [
                'manyToMany|Test\SourceEntity|Test\TargetEntity|testField' => [
                    'field_id'      => new FieldConfigId(
                        'extend',
                        'Test\SourceEntity',
                        'testField',
                        'manyToMany'
                    ),
                    'owner'         => true,
                    'target_entity' => 'Test\TargetEntity'
                ]
            ],
            $targetConfig->get('relation')
        );
    }

    /**
     * @param array  $values
     * @param string $type
     *
     * @return Config
     */
    protected function addConfigNewField($values = [], $type = 'string')
    {
        $resultValues = [
            'owner'      => ExtendScope::OWNER_CUSTOM,
            'state'      => ExtendScope::STATE_NEW,
            'is_deleted' => false,
        ];

        if (!empty($values)) {
            $resultValues = array_merge($resultValues, $values);
        }

        return $this->extendConfigProvider->addFieldConfig(
            'Test\SourceEntity',
            'testField',
            $type,
            $resultValues
        );
    }

    /**
     * @param array  $values
     * @param string $className
     *
     * @return Config
     */
    protected function addEntityConfig($values = [], $className = 'Test\SourceEntity')
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

        if (!empty($values)) {
            $resultValues = array_merge($resultValues, $values);
        }

        return $this->extendConfigProvider->addEntityConfig(
            $className,
            $resultValues
        );
    }
}
