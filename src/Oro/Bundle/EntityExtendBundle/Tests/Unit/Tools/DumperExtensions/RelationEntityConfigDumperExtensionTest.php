<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\Tools\DumperExtensions;

use Oro\Bundle\EntityConfigBundle\Config\Config;
use Oro\Bundle\EntityConfigBundle\Config\Id\EntityConfigId;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\EntityExtendBundle\Extend\FieldTypeHelper;
use Oro\Bundle\EntityExtendBundle\Tools\DumperExtensions\RelationEntityConfigDumperExtension;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendConfigDumper;

/**
 * @SuppressWarnings(PHPMD.ExcessiveClassLength)
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class RelationEntityConfigDumperExtensionTest extends \PHPUnit_Framework_TestCase
{
    /** @var RelationEntityConfigDumperExtension */
    protected $extension;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $configManager;

    /** @var array */
    protected $configs = [];

    public function setUp()
    {
        $this->configManager = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Config\ConfigManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->extension = new RelationEntityConfigDumperExtension(
            $this->configManager,
            new FieldTypeHelper(['enum' => 'manyToOne', 'multiEnum' => 'manyToMany'])
        );

        // will be filled by addEntityConfig and addConfigNewField
        $this->configs = [];

        $this->configManager->expects($this->any())
            ->method('getConfigs')
            ->with('extend')
            ->willReturnCallback(
                function ($scope, $className, $withHidden) {
                    return isset($this->configs[$className])
                        ? $this->configs[$className]
                        : [];
                }
            );
        $this->configManager->expects($this->any())
            ->method('getEntityConfig')
            ->with('extend')
            ->willReturnCallback(
                function ($scope, $className) {
                    $result = null;
                    if (isset($this->configs[null])) {
                        foreach ($this->configs[null] as $config) {
                            if ($config->getId()->getClassName() === $className) {
                                $result = $config;
                                break;
                            }
                        }
                    }

                    return $result;
                }
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
     * @dataProvider elementsOrderProvider
     */
    public function testReverseRelationAlreadyCreatedForManyToOne($reverseElements)
    {
        $selfRelations = [
            'manyToOne|Test\SourceEntity|Test\TargetEntity|rel_mto' => [
                'field_id'        => $this->getFieldId('Test\SourceEntity', 'rel_mto', 'manyToOne'),
                'owner'           => true,
                'target_entity'   => 'Test\TargetEntity',
                'target_field_id' => $this->getFieldId('Test\TargetEntity', 'rev_rel_mto', 'oneToMany')
            ]
        ];
        $targetRelations = [
            'manyToOne|Test\SourceEntity|Test\TargetEntity|rel_mto' => [
                'field_id'        => $this->getFieldId('Test\TargetEntity', 'rev_rel_mto', 'oneToMany'),
                'owner'           => false,
                'target_entity'   => 'Test\SourceEntity',
                'target_field_id' => $this->getFieldId('Test\SourceEntity', 'rel_mto', 'manyToOne')
            ]
        ];

        $selfConfig = $this->addEntityConfig(
            [
                'is_extend' => true,
                'state'     => ExtendScope::STATE_ACTIVE,
                'relation'  => $selfRelations,
            ],
            'Test\SourceEntity'
        );
        $targetConfig = $this->addEntityConfig(
            [
                'is_extend' => true,
                'state'     => ExtendScope::STATE_ACTIVE,
                'relation'  => $targetRelations,
            ],
            'Test\TargetEntity'
        );
        if ($reverseElements) {
            $this->configs[null] = array_reverse($this->configs[null]);
        }

        $this->addConfigNewField(
            [
                'target_entity' => 'Test\TargetEntity',
                'relation_key'  => 'manyToOne|Test\SourceEntity|Test\TargetEntity|rel_mto'
            ],
            'manyToOne',
            'Test\SourceEntity',
            'rel_mto'
        );
        $this->addConfigNewField(
            [
                'target_entity' => 'Test\SourceEntity',
                'relation_key'  => 'manyToOne|Test\SourceEntity|Test\TargetEntity|rel_mto'
            ],
            'oneToMany',
            'Test\TargetEntity',
            'rev_rel_mto'
        );

        $this->extension->preUpdate();

        $this->assertEquals($selfRelations, $selfConfig->get('relation'));
        $this->assertEquals($targetRelations, $targetConfig->get('relation'));
    }

    /**
     * @dataProvider elementsOrderProvider
     */
    public function testReverseRelationAlreadyCreatedForManyToMany($reverseElements)
    {
        $selfRelations = [
            'manyToMany|Test\SourceEntity|Test\TargetEntity|rel_mtm' => [
                'field_id'        => $this->getFieldId('Test\SourceEntity', 'rel_mtm', 'manyToMany'),
                'owner'           => true,
                'target_entity'   => 'Test\TargetEntity',
                'target_field_id' => $this->getFieldId('Test\TargetEntity', 'sourceentity_rel_mtm', 'manyToMany')
            ]
        ];
        $targetRelations = [
            'manyToMany|Test\SourceEntity|Test\TargetEntity|rel_mtm' => [
                'field_id'        => $this->getFieldId('Test\TargetEntity', 'sourceentity_rel_mtm', 'manyToMany'),
                'owner'           => false,
                'target_entity'   => 'Test\SourceEntity',
                'target_field_id' => $this->getFieldId('Test\SourceEntity', 'rel_mtm', 'manyToMany')
            ]
        ];

        $selfConfig = $this->addEntityConfig(
            [
                'is_extend' => true,
                'state'     => ExtendScope::STATE_ACTIVE,
                'relation'  => $selfRelations,
            ],
            'Test\SourceEntity'
        );
        $targetConfig = $this->addEntityConfig(
            [
                'is_extend' => true,
                'state'     => ExtendScope::STATE_ACTIVE,
                'relation'  => $targetRelations,
            ],
            'Test\TargetEntity'
        );
        if ($reverseElements) {
            $this->configs[null] = array_reverse($this->configs[null]);
        }

        $this->addConfigNewField(
            [
                'target_entity' => 'Test\TargetEntity',
                'relation_key'  => 'manyToMany|Test\SourceEntity|Test\TargetEntity|rel_mtm'
            ],
            'manyToMany',
            'Test\SourceEntity',
            'rel_mtm'
        );
        $this->addConfigNewField(
            [
                'target_entity' => 'Test\SourceEntity',
                'relation_key'  => 'manyToMany|Test\SourceEntity|Test\TargetEntity|rel_mtm'
            ],
            'manyToMany',
            'Test\TargetEntity',
            'sourceentity_rel_mtm'
        );

        $this->extension->preUpdate();

        $this->assertEquals($selfRelations, $selfConfig->get('relation'));
        $this->assertEquals($targetRelations, $targetConfig->get('relation'));
    }

    /**
     * @dataProvider elementsOrderProvider
     */
    public function testReverseRelationAlreadyCreatedForOneToMany($reverseElements)
    {
        $selfRelations = [
            'oneToMany|Test\SourceEntity|Test\TargetEntity|rel_otm' => [
                'field_id'        => $this->getFieldId('Test\SourceEntity', 'rel_otm', 'oneToMany'),
                'owner'           => false,
                'target_entity'   => 'Test\TargetEntity',
                'target_field_id' => $this->getFieldId('Test\TargetEntity', 'sourceentity_rel_otm', 'manyToOne')
            ]
        ];
        $targetRelations = [
            'oneToMany|Test\SourceEntity|Test\TargetEntity|rel_otm' => [
                'field_id'        => $this->getFieldId('Test\TargetEntity', 'sourceentity_rel_otm', 'manyToOne'),
                'owner'           => true,
                'target_entity'   => 'Test\SourceEntity',
                'target_field_id' => $this->getFieldId('Test\SourceEntity', 'rel_otm', 'oneToMany')
            ]
        ];

        $selfConfig = $this->addEntityConfig(
            [
                'is_extend' => true,
                'state'     => ExtendScope::STATE_ACTIVE,
                'relation'  => $selfRelations,
            ],
            'Test\SourceEntity'
        );
        $targetConfig = $this->addEntityConfig(
            [
                'is_extend' => true,
                'state'     => ExtendScope::STATE_ACTIVE,
                'relation'  => $targetRelations,
            ],
            'Test\TargetEntity'
        );
        if ($reverseElements) {
            $this->configs[null] = array_reverse($this->configs[null]);
        }

        $this->addConfigNewField(
            [
                'target_entity' => 'Test\TargetEntity',
                'relation_key'  => 'oneToMany|Test\SourceEntity|Test\TargetEntity|rel_otm'
            ],
            'oneToMany',
            'Test\SourceEntity',
            'rel_otm'
        );
        $this->addConfigNewField(
            [
                'target_entity' => 'Test\SourceEntity',
                'relation_key'  => 'oneToMany|Test\SourceEntity|Test\TargetEntity|rel_otm'
            ],
            'manyToOne',
            'Test\TargetEntity',
            'sourceentity_rel_otm'
        );

        $this->extension->preUpdate();

        $this->assertEquals($selfRelations, $selfConfig->get('relation'));
        $this->assertEquals($targetRelations, $targetConfig->get('relation'));
    }

    /**
     * @dataProvider elementsOrderProvider
     */
    public function testNoReverseRelationForManyToOne($reverseElements)
    {
        $selfRelations = [
            'manyToOne|Test\SourceEntity|Test\TargetEntity|rel_mto' => [
                'field_id'        => $this->getFieldId('Test\SourceEntity', 'rel_mto', 'manyToOne'),
                'owner'           => true,
                'target_entity'   => 'Test\TargetEntity',
                'target_field_id' => false
            ]
        ];
        $targetRelations = [
            'manyToOne|Test\SourceEntity|Test\TargetEntity|rel_mto' => [
                'field_id'        => false,
                'owner'           => false,
                'target_entity'   => 'Test\SourceEntity',
                'target_field_id' => $this->getFieldId('Test\SourceEntity', 'rel_mto', 'manyToOne')
            ]
        ];

        $selfConfig = $this->addEntityConfig(
            [
                'is_extend' => true,
                'state'     => ExtendScope::STATE_ACTIVE,
                'relation'  => $selfRelations,
            ],
            'Test\SourceEntity'
        );
        $targetConfig = $this->addEntityConfig(
            [
                'is_extend' => true,
                'state'     => ExtendScope::STATE_ACTIVE,
                'relation'  => $targetRelations,
            ],
            'Test\TargetEntity'
        );
        if ($reverseElements) {
            $this->configs[null] = array_reverse($this->configs[null]);
        }

        $this->addConfigNewField(
            [
                'target_entity' => 'Test\TargetEntity',
                'relation_key'  => 'manyToOne|Test\SourceEntity|Test\TargetEntity|rel_mto'
            ],
            'manyToOne',
            'Test\SourceEntity',
            'rel_mto'
        );

        $this->extension->preUpdate();

        $this->assertEquals($selfRelations, $selfConfig->get('relation'));
        $this->assertEquals($targetRelations, $targetConfig->get('relation'));
    }

    public function testNoReverseRelationForManyToOneAndSameOwningAndTarget()
    {
        $selfRelations = [
            'manyToOne|Test\SourceEntity|Test\SourceEntity|rel_mto'         => [
                'field_id'        => $this->getFieldId('Test\SourceEntity', 'rel_mto', 'manyToOne'),
                'owner'           => true,
                'target_entity'   => 'Test\SourceEntity',
                'target_field_id' => false
            ],
            'manyToOne|Test\SourceEntity|Test\SourceEntity|rel_mto|inverse' => [
                'field_id'        => false,
                'owner'           => false,
                'target_entity'   => 'Test\SourceEntity',
                'target_field_id' => $this->getFieldId('Test\SourceEntity', 'rel_mto', 'manyToOne')
            ]
        ];

        $selfConfig = $this->addEntityConfig(
            [
                'is_extend' => true,
                'state'     => ExtendScope::STATE_ACTIVE,
                'relation'  => $selfRelations,
            ],
            'Test\SourceEntity'
        );

        $this->addConfigNewField(
            [
                'target_entity' => 'Test\SourceEntity',
                'relation_key'  => 'manyToOne|Test\SourceEntity|Test\SourceEntity|rel_mto'
            ],
            'manyToOne',
            'Test\SourceEntity',
            'rel_mto'
        );

        $this->extension->preUpdate();

        $this->assertEquals($selfRelations, $selfConfig->get('relation'));
    }

    /**
     * @dataProvider elementsOrderProvider
     */
    public function testNoReverseRelationForManyToMany($reverseElements)
    {
        $selfRelations = [
            'manyToMany|Test\SourceEntity|Test\TargetEntity|rel_mtm' => [
                'field_id'        => $this->getFieldId('Test\SourceEntity', 'rel_mtm', 'manyToMany'),
                'owner'           => true,
                'target_entity'   => 'Test\TargetEntity',
                'target_field_id' => $this->getFieldId('Test\TargetEntity', 'sourceentity_rel_mtm', 'manyToMany')
            ]
        ];
        $targetRelations = [
            'manyToMany|Test\SourceEntity|Test\TargetEntity|rel_mtm' => [
                'field_id'        => $this->getFieldId('Test\TargetEntity', 'sourceentity_rel_mtm', 'manyToMany'),
                'owner'           => false,
                'target_entity'   => 'Test\SourceEntity',
                'target_field_id' => $this->getFieldId('Test\SourceEntity', 'rel_mtm', 'manyToMany')
            ]
        ];

        $selfConfig = $this->addEntityConfig(
            [
                'is_extend' => true,
                'state'     => ExtendScope::STATE_ACTIVE,
                'relation'  => $selfRelations,
            ],
            'Test\SourceEntity'
        );
        $targetConfig = $this->addEntityConfig(
            [
                'is_extend' => true,
                'state'     => ExtendScope::STATE_ACTIVE,
                'relation'  => $targetRelations,
            ],
            'Test\TargetEntity'
        );
        if ($reverseElements) {
            $this->configs[null] = array_reverse($this->configs[null]);
        }

        $this->addConfigNewField(
            [
                'target_entity' => 'Test\TargetEntity',
                'relation_key'  => 'manyToMany|Test\SourceEntity|Test\TargetEntity|rel_mtm'
            ],
            'manyToMany',
            'Test\SourceEntity',
            'rel_mtm'
        );

        $this->extension->preUpdate();

        $this->assertEquals($selfRelations, $selfConfig->get('relation'));
        $this->assertEquals($targetRelations, $targetConfig->get('relation'));
    }

    public function testNoReverseRelationForManyToManyAndSameOwningAndTarget()
    {
        $selfRelations = [
            'manyToMany|Test\SourceEntity|Test\SourceEntity|rel_mtm'         => [
                'field_id'        => $this->getFieldId('Test\SourceEntity', 'rel_mtm', 'manyToMany'),
                'owner'           => true,
                'target_entity'   => 'Test\SourceEntity',
                'target_field_id' => $this->getFieldId('Test\SourceEntity', 'sourceentity_rel_mtm', 'manyToMany')
            ],
            'manyToMany|Test\SourceEntity|Test\SourceEntity|rel_mtm|inverse' => [
                'field_id'        => $this->getFieldId('Test\SourceEntity', 'sourceentity_rel_mtm', 'manyToMany'),
                'owner'           => false,
                'target_entity'   => 'Test\SourceEntity',
                'target_field_id' => $this->getFieldId('Test\SourceEntity', 'rel_mtm', 'manyToMany')
            ]
        ];

        $selfConfig = $this->addEntityConfig(
            [
                'is_extend' => true,
                'state'     => ExtendScope::STATE_ACTIVE,
                'relation'  => $selfRelations,
            ],
            'Test\SourceEntity'
        );

        $this->addConfigNewField(
            [
                'target_entity' => 'Test\SourceEntity',
                'relation_key'  => 'manyToMany|Test\SourceEntity|Test\SourceEntity|rel_mtm'
            ],
            'manyToMany',
            'Test\SourceEntity',
            'rel_mtm'
        );

        $this->extension->preUpdate();

        $this->assertEquals($selfRelations, $selfConfig->get('relation'));
    }

    /**
     * @dataProvider elementsOrderProvider
     */
    public function testNoReverseRelationForOneToMany($reverseElements)
    {
        $selfRelations = [
            'oneToMany|Test\SourceEntity|Test\TargetEntity|rel_otm' => [
                'field_id'        => $this->getFieldId('Test\SourceEntity', 'rel_otm', 'oneToMany'),
                'owner'           => true,
                'target_entity'   => 'Test\TargetEntity',
                'target_field_id' => $this->getFieldId('Test\TargetEntity', 'sourceentity_rel_otm', 'manyToOne')
            ]
        ];
        $targetRelations = [
            'oneToMany|Test\SourceEntity|Test\TargetEntity|rel_otm' => [
                'field_id'        => $this->getFieldId('Test\TargetEntity', 'sourceentity_rel_otm', 'manyToOne'),
                'owner'           => false,
                'target_entity'   => 'Test\SourceEntity',
                'target_field_id' => $this->getFieldId('Test\SourceEntity', 'rel_otm', 'oneToMany')
            ]
        ];

        $selfConfig = $this->addEntityConfig(
            [
                'is_extend' => true,
                'state'     => ExtendScope::STATE_ACTIVE,
                'relation'  => $selfRelations,
            ],
            'Test\SourceEntity'
        );
        $targetConfig = $this->addEntityConfig(
            [
                'is_extend' => true,
                'state'     => ExtendScope::STATE_ACTIVE,
                'relation'  => $targetRelations,
            ],
            'Test\TargetEntity'
        );
        if ($reverseElements) {
            $this->configs[null] = array_reverse($this->configs[null]);
        }

        $this->addConfigNewField(
            [
                'target_entity' => 'Test\TargetEntity',
                'relation_key'  => 'oneToMany|Test\SourceEntity|Test\TargetEntity|rel_otm'
            ],
            'oneToMany',
            'Test\SourceEntity',
            'rel_otm'
        );

        $this->extension->preUpdate();

        $this->assertEquals($selfRelations, $selfConfig->get('relation'));
        $this->assertEquals($targetRelations, $targetConfig->get('relation'));
    }

    public function testNoReverseRelationForOneToManyAndSameOwningAndTarget()
    {
        $selfRelations = [
            'oneToMany|Test\SourceEntity|Test\SourceEntity|rel_otm'         => [
                'field_id'        => $this->getFieldId('Test\SourceEntity', 'rel_otm', 'oneToMany'),
                'owner'           => true,
                'target_entity'   => 'Test\SourceEntity',
                'target_field_id' => $this->getFieldId('Test\SourceEntity', 'sourceentity_rel_otm', 'manyToOne')
            ],
            'oneToMany|Test\SourceEntity|Test\SourceEntity|rel_otm|inverse' => [
                'field_id'        => $this->getFieldId('Test\SourceEntity', 'sourceentity_rel_otm', 'manyToOne'),
                'owner'           => false,
                'target_entity'   => 'Test\SourceEntity',
                'target_field_id' => $this->getFieldId('Test\SourceEntity', 'rel_otm', 'oneToMany')
            ]
        ];

        $selfConfig = $this->addEntityConfig(
            [
                'is_extend' => true,
                'state'     => ExtendScope::STATE_ACTIVE,
                'relation'  => $selfRelations,
            ],
            'Test\SourceEntity'
        );

        $this->addConfigNewField(
            [
                'target_entity' => 'Test\SourceEntity',
                'relation_key'  => 'oneToMany|Test\SourceEntity|Test\SourceEntity|rel_otm'
            ],
            'oneToMany',
            'Test\SourceEntity',
            'rel_otm'
        );

        $this->extension->preUpdate();

        $this->assertEquals($selfRelations, $selfConfig->get('relation'));
    }

    /**
     * @dataProvider elementsOrderProvider
     */
    public function testSelfRelationShouldBeCreatedForManyToOneBidirectional($reverseElements)
    {
        $expectedSelfRelations = [
            'manyToOne|Test\SourceEntity|Test\TargetEntity|rel_mto' => [
                'field_id'        => $this->getFieldId('Test\SourceEntity', 'rel_mto', 'manyToOne'),
                'owner'           => true,
                'target_entity'   => 'Test\TargetEntity',
                'target_field_id' => $this->getFieldId('Test\TargetEntity', 'rev_rel_mto', 'oneToMany'),
                'cascade'         => ['persist', 'remove'],
                'on_delete'       => 'CASCADE',
                'nullable'        => true
            ]
        ];
        $targetRelations = [
            'manyToOne|Test\SourceEntity|Test\TargetEntity|rel_mto' => [
                'field_id'        => $this->getFieldId('Test\TargetEntity', 'rev_rel_mto', 'oneToMany'),
                'owner'           => false,
                'target_entity'   => 'Test\SourceEntity',
                'target_field_id' => $this->getFieldId('Test\SourceEntity', 'rel_mto', 'manyToOne')
            ]
        ];

        $selfConfig = $this->addEntityConfig(
            [
                'is_extend' => true,
                'state'     => ExtendScope::STATE_ACTIVE
            ],
            'Test\SourceEntity'
        );
        $targetConfig = $this->addEntityConfig(
            [
                'is_extend' => true,
                'state'     => ExtendScope::STATE_ACTIVE,
                'relation'  => $targetRelations
            ],
            'Test\TargetEntity'
        );
        if ($reverseElements) {
            $this->configs[null] = array_reverse($this->configs[null]);
        }

        $this->addConfigNewField(
            [
                'target_entity' => 'Test\TargetEntity',
                'relation_key'  => 'manyToOne|Test\SourceEntity|Test\TargetEntity|rel_mto',
                'cascade'       => ['persist', 'remove'],
                'on_delete'     => 'CASCADE',
                'nullable'      => true,
                'bidirectional' => true
            ],
            'manyToOne',
            'Test\SourceEntity',
            'rel_mto'
        );
        $this->addConfigNewField(
            [
                'target_entity' => 'Test\SourceEntity',
                'relation_key'  => 'manyToOne|Test\SourceEntity|Test\TargetEntity|rel_mto'
            ],
            'oneToMany',
            'Test\TargetEntity',
            'rev_rel_mto'
        );

        $this->extension->preUpdate();

        $this->assertEquals($expectedSelfRelations, $selfConfig->get('relation'));
        $this->assertEquals($targetRelations, $targetConfig->get('relation'));
    }

    /**
     * @dataProvider elementsOrderProvider
     */
    public function testSelfRelationShouldBeCreatedForManyToOneAndSameOwningAndTarget($reverseElements)
    {
        $selfRelations = [
            'manyToOne|Test\SourceEntity|Test\SourceEntity|rel_mto|inverse' => [
                'field_id'        => $this->getFieldId('Test\SourceEntity', 'rev_rel_mto', 'oneToMany'),
                'owner'           => false,
                'target_entity'   => 'Test\SourceEntity',
                'target_field_id' => $this->getFieldId('Test\SourceEntity', 'rel_mto', 'manyToOne')
            ]
        ];
        $expectedSelfRelations = [
            'manyToOne|Test\SourceEntity|Test\SourceEntity|rel_mto'         => [
                'field_id'        => $this->getFieldId('Test\SourceEntity', 'rel_mto', 'manyToOne'),
                'owner'           => true,
                'target_entity'   => 'Test\SourceEntity',
                'target_field_id' => $this->getFieldId('Test\SourceEntity', 'rev_rel_mto', 'oneToMany'),
                'cascade'         => ['persist', 'remove'],
                'on_delete'       => 'CASCADE',
                'nullable'        => true
            ],
            'manyToOne|Test\SourceEntity|Test\SourceEntity|rel_mto|inverse' => [
                'field_id'        => $this->getFieldId('Test\SourceEntity', 'rev_rel_mto', 'oneToMany'),
                'owner'           => false,
                'target_entity'   => 'Test\SourceEntity',
                'target_field_id' => $this->getFieldId('Test\SourceEntity', 'rel_mto', 'manyToOne')
            ]
        ];

        $selfConfig = $this->addEntityConfig(
            [
                'is_extend' => true,
                'state'     => ExtendScope::STATE_ACTIVE,
                'relation'  => $selfRelations
            ],
            'Test\SourceEntity'
        );

        $this->addConfigNewField(
            [
                'target_entity' => 'Test\SourceEntity',
                'relation_key'  => 'manyToOne|Test\SourceEntity|Test\SourceEntity|rel_mto',
                'cascade'       => ['persist', 'remove'],
                'on_delete'     => 'CASCADE',
                'nullable'      => true,
                'bidirectional' => true
            ],
            'manyToOne',
            'Test\SourceEntity',
            'rel_mto'
        );
        $this->addConfigNewField(
            [
                'target_entity' => 'Test\SourceEntity',
                'relation_key'  => 'manyToOne|Test\SourceEntity|Test\SourceEntity|rel_mto|inverse'
            ],
            'oneToMany',
            'Test\SourceEntity',
            'rev_rel_mto'
        );
        if ($reverseElements) {
            $this->configs['Test\SourceEntity'] = array_reverse($this->configs['Test\SourceEntity']);
        }

        $this->extension->preUpdate();

        $this->assertEquals($expectedSelfRelations, $selfConfig->get('relation'));
    }

    /**
     * @dataProvider elementsOrderProvider
     */
    public function testSelfRelationShouldBeCreatedForManyToMany($reverseElements)
    {
        $expectedSelfRelations = [
            'manyToMany|Test\SourceEntity|Test\TargetEntity|rel_mtm' => [
                'field_id'        => $this->getFieldId('Test\SourceEntity', 'rel_mtm', 'manyToMany'),
                'owner'           => true,
                'target_entity'   => 'Test\TargetEntity',
                'target_field_id' => $this->getFieldId('Test\TargetEntity', 'sourceentity_rel_mtm', 'manyToMany'),
                'cascade'         => ['persist', 'remove']
            ]
        ];
        $targetRelations = [
            'manyToMany|Test\SourceEntity|Test\TargetEntity|rel_mtm' => [
                'field_id'        => $this->getFieldId('Test\TargetEntity', 'sourceentity_rel_mtm', 'manyToMany'),
                'owner'           => false,
                'target_entity'   => 'Test\SourceEntity',
                'target_field_id' => $this->getFieldId('Test\SourceEntity', 'rel_mtm', 'manyToMany')
            ]
        ];

        $selfConfig = $this->addEntityConfig(
            [
                'is_extend' => true,
                'state'     => ExtendScope::STATE_ACTIVE
            ],
            'Test\SourceEntity'
        );
        $targetConfig = $this->addEntityConfig(
            [
                'is_extend' => true,
                'state'     => ExtendScope::STATE_ACTIVE,
                'relation'  => $targetRelations,
            ],
            'Test\TargetEntity'
        );
        if ($reverseElements) {
            $this->configs[null] = array_reverse($this->configs[null]);
        }

        $this->addConfigNewField(
            [
                'target_entity' => 'Test\TargetEntity',
                'relation_key'  => 'manyToMany|Test\SourceEntity|Test\TargetEntity|rel_mtm',
                'cascade'       => ['persist', 'remove'],
                'bidirectional' => true
            ],
            'manyToMany',
            'Test\SourceEntity',
            'rel_mtm'
        );
        $this->addConfigNewField(
            [
                'target_entity' => 'Test\SourceEntity',
                'relation_key'  => 'manyToMany|Test\SourceEntity|Test\TargetEntity|rel_mtm'
            ],
            'manyToMany',
            'Test\TargetEntity',
            'sourceentity_rel_mtm'
        );

        $this->extension->preUpdate();

        $this->assertEquals($expectedSelfRelations, $selfConfig->get('relation'));
        $this->assertEquals($targetRelations, $targetConfig->get('relation'));
    }

    /**
     * @dataProvider elementsOrderProvider
     */
    public function testSelfRelationShouldBeCreatedForManyToManyAndSameOwningAndTarget($reverseElements)
    {
        $expectedSelfRelations = [
            'manyToMany|Test\SourceEntity|Test\SourceEntity|rel_mtm'         => [
                'field_id'        => $this->getFieldId('Test\SourceEntity', 'rel_mtm', 'manyToMany'),
                'owner'           => true,
                'target_entity'   => 'Test\SourceEntity',
                'target_field_id' => $this->getFieldId('Test\SourceEntity', 'sourceentity_rel_mtm', 'manyToMany'),
                'cascade'         => ['persist', 'remove']
            ],
            'manyToMany|Test\SourceEntity|Test\SourceEntity|rel_mtm|inverse' => [
                'field_id'        => $this->getFieldId('Test\SourceEntity', 'sourceentity_rel_mtm', 'manyToMany'),
                'owner'           => false,
                'target_entity'   => 'Test\SourceEntity',
                'target_field_id' => $this->getFieldId('Test\SourceEntity', 'rel_mtm', 'manyToMany')
            ]
        ];
        $selfRelations = [
            'manyToMany|Test\SourceEntity|Test\SourceEntity|rel_mtm|inverse' => [
                'field_id'        => $this->getFieldId('Test\SourceEntity', 'sourceentity_rel_mtm', 'manyToMany'),
                'owner'           => false,
                'target_entity'   => 'Test\SourceEntity',
                'target_field_id' => $this->getFieldId('Test\SourceEntity', 'rel_mtm', 'manyToMany')
            ]
        ];

        $selfConfig = $this->addEntityConfig(
            [
                'is_extend' => true,
                'state'     => ExtendScope::STATE_ACTIVE,
                'relation'  => $selfRelations
            ],
            'Test\SourceEntity'
        );

        $this->addConfigNewField(
            [
                'target_entity' => 'Test\SourceEntity',
                'relation_key'  => 'manyToMany|Test\SourceEntity|Test\SourceEntity|rel_mtm',
                'cascade'       => ['persist', 'remove'],
                'bidirectional' => true
            ],
            'manyToMany',
            'Test\SourceEntity',
            'rel_mtm'
        );
        $this->addConfigNewField(
            [
                'target_entity' => 'Test\SourceEntity',
                'relation_key'  => 'manyToMany|Test\SourceEntity|Test\SourceEntity|rel_mtm|inverse'
            ],
            'manyToMany',
            'Test\SourceEntity',
            'sourceentity_rel_mtm'
        );
        if ($reverseElements) {
            $this->configs['Test\SourceEntity'] = array_reverse($this->configs['Test\SourceEntity']);
        }

        $this->extension->preUpdate();

        $this->assertEquals($expectedSelfRelations, $selfConfig->get('relation'));
    }

    /**
     * @dataProvider elementsOrderProvider
     */
    public function testSelfRelationShouldBeCreatedForOneToMany($reverseElements)
    {
        $expectedSelfRelations = [
            'oneToMany|Test\SourceEntity|Test\TargetEntity|rel_otm' => [
                'field_id'        => $this->getFieldId('Test\SourceEntity', 'rel_otm', 'oneToMany'),
                'owner'           => false,
                'target_entity'   => 'Test\TargetEntity',
                'target_field_id' => $this->getFieldId('Test\TargetEntity', 'sourceentity_rel_otm', 'manyToOne'),
                'cascade'         => ['persist', 'remove']
            ]
        ];
        $targetRelations = [
            'oneToMany|Test\SourceEntity|Test\TargetEntity|rel_otm' => [
                'field_id'        => $this->getFieldId('Test\TargetEntity', 'sourceentity_rel_otm', 'manyToOne'),
                'owner'           => true,
                'target_entity'   => 'Test\SourceEntity',
                'target_field_id' => $this->getFieldId('Test\SourceEntity', 'rel_otm', 'oneToMany')
            ]
        ];

        $selfConfig = $this->addEntityConfig(
            [
                'is_extend' => true,
                'state'     => ExtendScope::STATE_ACTIVE
            ],
            'Test\SourceEntity'
        );
        $targetConfig = $this->addEntityConfig(
            [
                'is_extend' => true,
                'state'     => ExtendScope::STATE_ACTIVE,
                'relation'  => $targetRelations,
            ],
            'Test\TargetEntity'
        );
        if ($reverseElements) {
            $this->configs[null] = array_reverse($this->configs[null]);
        }

        $this->addConfigNewField(
            [
                'target_entity' => 'Test\TargetEntity',
                'relation_key'  => 'oneToMany|Test\SourceEntity|Test\TargetEntity|rel_otm',
                'cascade'       => ['persist', 'remove'],
                'bidirectional' => true
            ],
            'oneToMany',
            'Test\SourceEntity',
            'rel_otm'
        );
        $this->addConfigNewField(
            [
                'target_entity' => 'Test\SourceEntity',
                'relation_key'  => 'oneToMany|Test\SourceEntity|Test\TargetEntity|rel_otm'
            ],
            'manyToOne',
            'Test\TargetEntity',
            'sourceentity_rel_otm'
        );

        $this->extension->preUpdate();

        $this->assertEquals($expectedSelfRelations, $selfConfig->get('relation'));
        $this->assertEquals($targetRelations, $targetConfig->get('relation'));
    }

    /**
     * @dataProvider elementsOrderProvider
     */
    public function testSelfRelationShouldBeCreatedForOneToManyAndSameOwningAndTarget($reverseElements)
    {
        $expectedSelfRelations = [
            'oneToMany|Test\SourceEntity|Test\SourceEntity|rel_otm'         => [
                'field_id'        => $this->getFieldId('Test\SourceEntity', 'rel_otm', 'oneToMany'),
                'owner'           => false,
                'target_entity'   => 'Test\SourceEntity',
                'target_field_id' => $this->getFieldId('Test\SourceEntity', 'sourceentity_rel_otm', 'manyToOne'),
                'cascade'         => ['persist', 'remove']
            ],
            'oneToMany|Test\SourceEntity|Test\SourceEntity|rel_otm|inverse' => [
                'field_id'        => $this->getFieldId('Test\SourceEntity', 'sourceentity_rel_otm', 'manyToOne'),
                'owner'           => true,
                'target_entity'   => 'Test\SourceEntity',
                'target_field_id' => $this->getFieldId('Test\SourceEntity', 'rel_otm', 'oneToMany')
            ]
        ];
        $selfRelations = [
            'oneToMany|Test\SourceEntity|Test\SourceEntity|rel_otm|inverse' => [
                'field_id'        => $this->getFieldId('Test\SourceEntity', 'sourceentity_rel_otm', 'manyToOne'),
                'owner'           => true,
                'target_entity'   => 'Test\SourceEntity',
                'target_field_id' => $this->getFieldId('Test\SourceEntity', 'rel_otm', 'oneToMany')
            ]
        ];

        $selfConfig = $this->addEntityConfig(
            [
                'is_extend' => true,
                'state'     => ExtendScope::STATE_ACTIVE,
                'relation'  => $selfRelations
            ],
            'Test\SourceEntity'
        );

        $this->addConfigNewField(
            [
                'target_entity' => 'Test\SourceEntity',
                'relation_key'  => 'oneToMany|Test\SourceEntity|Test\SourceEntity|rel_otm',
                'cascade'       => ['persist', 'remove'],
                'bidirectional' => true
            ],
            'oneToMany',
            'Test\SourceEntity',
            'rel_otm'
        );
        $this->addConfigNewField(
            [
                'target_entity' => 'Test\SourceEntity',
                'relation_key'  => 'oneToMany|Test\SourceEntity|Test\SourceEntity|rel_otm|inverse'
            ],
            'manyToOne',
            'Test\SourceEntity',
            'sourceentity_rel_otm'
        );
        if ($reverseElements) {
            $this->configs['Test\SourceEntity'] = array_reverse($this->configs['Test\SourceEntity']);
        }

        $this->extension->preUpdate();

        $this->assertEquals($expectedSelfRelations, $selfConfig->get('relation'));
    }

    public function testUnidirectionalRelationShouldBeCreatedForManyToOne()
    {
        $expectedSelfRelations = [
            'manyToOne|Test\SourceEntity|Test\TargetEntity|rel_mto' => [
                'field_id'        => $this->getFieldId('Test\SourceEntity', 'rel_mto', 'manyToOne'),
                'owner'           => true,
                'target_entity'   => 'Test\TargetEntity',
                'target_field_id' => false
            ]
        ];

        $selfConfig = $this->addEntityConfig(
            [
                'is_extend' => true,
                'state'     => ExtendScope::STATE_ACTIVE
            ],
            'Test\SourceEntity'
        );
        $targetConfig = $this->addEntityConfig(
            [
                'is_extend' => true,
                'state'     => ExtendScope::STATE_ACTIVE
            ],
            'Test\TargetEntity'
        );

        $this->addConfigNewField(
            [
                'target_entity' => 'Test\TargetEntity',
                'relation_key'  => 'manyToOne|Test\SourceEntity|Test\TargetEntity|rel_mto'
            ],
            'manyToOne',
            'Test\SourceEntity',
            'rel_mto'
        );

        $this->extension->preUpdate();

        $this->assertEquals($expectedSelfRelations, $selfConfig->get('relation'));
        // target relation should not be created in unidirectional relation
        $this->assertEquals([], $targetConfig->get('relation'));
    }

    public function testUnidirectionalRelationShouldBeCreatedForManyToOneAndSameOwningAndTarget()
    {
        $expectedSelfRelations = [
            'manyToOne|Test\SourceEntity|Test\SourceEntity|rel_mto'         => [
                'field_id'        => $this->getFieldId('Test\SourceEntity', 'rel_mto', 'manyToOne'),
                'owner'           => true,
                'target_entity'   => 'Test\SourceEntity',
                'target_field_id' => false
            ],
        ];

        $selfConfig = $this->addEntityConfig(
            [
                'is_extend' => true,
                'state'     => ExtendScope::STATE_ACTIVE
            ],
            'Test\SourceEntity'
        );

        $this->addConfigNewField(
            [
                'target_entity' => 'Test\SourceEntity',
                'relation_key'  => 'manyToOne|Test\SourceEntity|Test\SourceEntity|rel_mto'
            ],
            'manyToOne',
            'Test\SourceEntity',
            'rel_mto'
        );

        $this->extension->preUpdate();

        $this->assertEquals($expectedSelfRelations, $selfConfig->get('relation'));
    }

    public function testUnidirectionalRelationShouldBeCreatedForManyToMany()
    {
        $expectedSelfRelations = [
            'manyToMany|Test\SourceEntity|Test\TargetEntity|rel_mtm' => [
                'field_id'        => $this->getFieldId('Test\SourceEntity', 'rel_mtm', 'manyToMany'),
                'owner'           => true,
                'target_entity'   => 'Test\TargetEntity',
                'target_field_id' => false,
            ]
        ];

        $selfConfig = $this->addEntityConfig(
            [
                'is_extend' => true,
                'state'     => ExtendScope::STATE_ACTIVE
            ],
            'Test\SourceEntity'
        );
        $targetConfig = $this->addEntityConfig(
            [
                'is_extend' => true,
                'state'     => ExtendScope::STATE_ACTIVE
            ],
            'Test\TargetEntity'
        );

        $this->addConfigNewField(
            [
                'target_entity' => 'Test\TargetEntity',
                'relation_key'  => 'manyToMany|Test\SourceEntity|Test\TargetEntity|rel_mtm'
            ],
            'manyToMany',
            'Test\SourceEntity',
            'rel_mtm'
        );

        $this->extension->preUpdate();

        $this->assertEquals($expectedSelfRelations, $selfConfig->get('relation'));
        // target relation should not be created in unidirectional relation
        $this->assertEquals([], $targetConfig->get('relation'));
    }

    public function testUnidirectionalRelationShouldBeCreatedForManyToManyAndSameOwningAndTarget()
    {
        $expectedSelfRelations = [
            'manyToMany|Test\SourceEntity|Test\SourceEntity|rel_mtm'         => [
                'field_id'        => $this->getFieldId('Test\SourceEntity', 'rel_mtm', 'manyToMany'),
                'owner'           => true,
                'target_entity'   => 'Test\SourceEntity',
                'target_field_id' => false, // unidirectional relations have empty `target_field_id`
            ],
        ];

        $selfConfig = $this->addEntityConfig(
            [
                'is_extend' => true,
                'state'     => ExtendScope::STATE_ACTIVE
            ],
            'Test\SourceEntity'
        );

        $this->addConfigNewField(
            [
                'target_entity' => 'Test\SourceEntity',
                'relation_key'  => 'manyToMany|Test\SourceEntity|Test\SourceEntity|rel_mtm'
            ],
            'manyToMany',
            'Test\SourceEntity',
            'rel_mtm'
        );

        $this->extension->preUpdate();

        $this->assertEquals($expectedSelfRelations, $selfConfig->get('relation'));
    }

    /**
     * @dataProvider elementsOrderProvider
     */
    public function testCompleteSelfRelationForManyToOne($reverseElements)
    {
        $expectedSelfRelations = [
            'manyToOne|Test\SourceEntity|Test\TargetEntity|rel_mto' => [
                'field_id'        => $this->getFieldId('Test\SourceEntity', 'rel_mto', 'manyToOne'),
                'owner'           => true,
                'target_entity'   => 'Test\TargetEntity',
                'target_field_id' => $this->getFieldId('Test\TargetEntity', 'rev_rel_mto', 'oneToMany')
            ]
        ];
        $selfRelations = [
            'manyToOne|Test\SourceEntity|Test\TargetEntity|rel_mto' => [
                'target_field_id' => $this->getFieldId('Test\TargetEntity', 'rev_rel_mto', 'oneToMany')
            ]
        ];
        $targetRelations = [
            'manyToOne|Test\SourceEntity|Test\TargetEntity|rel_mto' => [
                'field_id'        => $this->getFieldId('Test\TargetEntity', 'rev_rel_mto', 'oneToMany'),
                'owner'           => false,
                'target_entity'   => 'Test\SourceEntity',
                'target_field_id' => $this->getFieldId('Test\SourceEntity', 'rel_mto', 'manyToOne')
            ]
        ];

        $selfConfig = $this->addEntityConfig(
            [
                'is_extend' => true,
                'state'     => ExtendScope::STATE_ACTIVE,
                'relation'  => $selfRelations,
            ],
            'Test\SourceEntity'
        );
        $targetConfig = $this->addEntityConfig(
            [
                'is_extend' => true,
                'state'     => ExtendScope::STATE_ACTIVE,
                'relation'  => $targetRelations,
            ],
            'Test\TargetEntity'
        );
        if ($reverseElements) {
            $this->configs[null] = array_reverse($this->configs[null]);
        }

        $this->addConfigNewField(
            [
                'target_entity' => 'Test\TargetEntity',
                'relation_key'  => 'manyToOne|Test\SourceEntity|Test\TargetEntity|rel_mto'
            ],
            'manyToOne',
            'Test\SourceEntity',
            'rel_mto'
        );
        $this->addConfigNewField(
            [
                'target_entity' => 'Test\SourceEntity',
                'relation_key'  => 'manyToOne|Test\SourceEntity|Test\TargetEntity|rel_mto'
            ],
            'oneToMany',
            'Test\TargetEntity',
            'rev_rel_mto'
        );

        $this->extension->preUpdate();

        $this->assertEquals($expectedSelfRelations, $selfConfig->get('relation'));
        $this->assertEquals($targetRelations, $targetConfig->get('relation'));
    }

    /**
     * @dataProvider elementsOrderProvider
     */
    public function testCompleteSelfRelationForManyToOneAndSameOwningAndTarget($reverseElements)
    {
        $expectedSelfRelations = [
            'manyToOne|Test\SourceEntity|Test\SourceEntity|rel_mto' => [
                'field_id'        => $this->getFieldId('Test\SourceEntity', 'rel_mto', 'manyToOne'),
                'owner'           => true,
                'target_entity'   => 'Test\SourceEntity',
                'target_field_id' => $this->getFieldId('Test\SourceEntity', 'rev_rel_mto', 'oneToMany')
            ],
            'manyToOne|Test\SourceEntity|Test\SourceEntity|rel_mto|inverse' => [
                'field_id'        => $this->getFieldId('Test\SourceEntity', 'rev_rel_mto', 'oneToMany'),
                'owner'           => false,
                'target_entity'   => 'Test\SourceEntity',
                'target_field_id' => $this->getFieldId('Test\SourceEntity', 'rel_mto', 'manyToOne')
            ]
        ];
        $selfRelations = [
            'manyToOne|Test\SourceEntity|Test\SourceEntity|rel_mto' => [
                'target_field_id' => $this->getFieldId('Test\SourceEntity', 'rev_rel_mto', 'oneToMany')
            ],
            'manyToOne|Test\SourceEntity|Test\SourceEntity|rel_mto|inverse' => [
                'field_id'        => $this->getFieldId('Test\SourceEntity', 'rev_rel_mto', 'oneToMany'),
                'owner'           => false,
                'target_entity'   => 'Test\SourceEntity',
                'target_field_id' => $this->getFieldId('Test\SourceEntity', 'rel_mto', 'manyToOne')
            ]
        ];

        $selfConfig = $this->addEntityConfig(
            [
                'is_extend' => true,
                'state'     => ExtendScope::STATE_ACTIVE,
                'relation'  => $selfRelations,
            ],
            'Test\SourceEntity'
        );

        $this->addConfigNewField(
            [
                'target_entity' => 'Test\SourceEntity',
                'relation_key'  => 'manyToOne|Test\SourceEntity|Test\SourceEntity|rel_mto'
            ],
            'manyToOne',
            'Test\SourceEntity',
            'rel_mto'
        );
        $this->addConfigNewField(
            [
                'target_entity' => 'Test\SourceEntity',
                'relation_key'  => 'manyToOne|Test\SourceEntity|Test\SourceEntity|rel_mto|inverse'
            ],
            'oneToMany',
            'Test\SourceEntity',
            'rev_rel_mto'
        );
        if ($reverseElements) {
            $this->configs['Test\SourceEntity'] = array_reverse($this->configs['Test\SourceEntity']);
        }

        $this->extension->preUpdate();

        $this->assertEquals($expectedSelfRelations, $selfConfig->get('relation'));
    }

    /**
     * @dataProvider elementsOrderProvider
     */
    public function testCompleteSelfRelationForManyToMany($reverseElements)
    {
        $expectedSelfRelations = [
            'manyToMany|Test\SourceEntity|Test\TargetEntity|rel_mtm' => [
                'field_id'        => $this->getFieldId('Test\SourceEntity', 'rel_mtm', 'manyToMany'),
                'owner'           => true,
                'target_entity'   => 'Test\TargetEntity',
                'target_field_id' => $this->getFieldId('Test\TargetEntity', 'rev_rel_mtm', 'manyToMany')
            ]
        ];
        $selfRelations = [
            'manyToMany|Test\SourceEntity|Test\TargetEntity|rel_mtm' => [
                'target_field_id' => $this->getFieldId('Test\TargetEntity', 'rev_rel_mtm', 'manyToMany')
            ]
        ];
        $targetRelations = [
            'manyToMany|Test\SourceEntity|Test\TargetEntity|rel_mtm' => [
                'field_id'        => $this->getFieldId('Test\TargetEntity', 'rev_rel_mtm', 'manyToMany'),
                'owner'           => false,
                'target_entity'   => 'Test\SourceEntity',
                'target_field_id' => $this->getFieldId('Test\SourceEntity', 'rel_mtm', 'manyToMany')
            ]
        ];

        $selfConfig = $this->addEntityConfig(
            [
                'is_extend' => true,
                'state'     => ExtendScope::STATE_ACTIVE,
                'relation'  => $selfRelations,
            ],
            'Test\SourceEntity'
        );
        $targetConfig = $this->addEntityConfig(
            [
                'is_extend' => true,
                'state'     => ExtendScope::STATE_ACTIVE,
                'relation'  => $targetRelations,
            ],
            'Test\TargetEntity'
        );
        if ($reverseElements) {
            $this->configs[null] = array_reverse($this->configs[null]);
        }

        $this->addConfigNewField(
            [
                'target_entity' => 'Test\TargetEntity',
                'relation_key'  => 'manyToMany|Test\SourceEntity|Test\TargetEntity|rel_mtm'
            ],
            'manyToMany',
            'Test\SourceEntity',
            'rel_mtm'
        );
        $this->addConfigNewField(
            [
                'target_entity' => 'Test\SourceEntity',
                'relation_key'  => 'manyToMany|Test\SourceEntity|Test\TargetEntity|rel_mtm'
            ],
            'manyToMany',
            'Test\TargetEntity',
            'rev_rel_mtm'
        );

        $this->extension->preUpdate();

        $this->assertEquals($expectedSelfRelations, $selfConfig->get('relation'));
        $this->assertEquals($targetRelations, $targetConfig->get('relation'));
    }

    /**
     * @dataProvider elementsOrderProvider
     */
    public function testCompleteSelfRelationForManyToManyAndSameOwningAndTarget($reverseElements)
    {
        $expectedSelfRelations = [
            'manyToMany|Test\SourceEntity|Test\SourceEntity|rel_mtm' => [
                'field_id'        => $this->getFieldId('Test\SourceEntity', 'rel_mtm', 'manyToMany'),
                'owner'           => true,
                'target_entity'   => 'Test\SourceEntity',
                'target_field_id' => $this->getFieldId('Test\SourceEntity', 'rev_rel_mtm', 'manyToMany')
            ],
            'manyToMany|Test\SourceEntity|Test\SourceEntity|rel_mtm|inverse' => [
                'field_id'        => $this->getFieldId('Test\SourceEntity', 'rev_rel_mtm', 'manyToMany'),
                'owner'           => false,
                'target_entity'   => 'Test\SourceEntity',
                'target_field_id' => $this->getFieldId('Test\SourceEntity', 'rel_mtm', 'manyToMany')
            ]
        ];
        $selfRelations = [
            'manyToMany|Test\SourceEntity|Test\SourceEntity|rel_mtm' => [
                'target_field_id' => $this->getFieldId('Test\SourceEntity', 'rev_rel_mtm', 'manyToMany')
            ],
            'manyToMany|Test\SourceEntity|Test\SourceEntity|rel_mtm|inverse' => [
                'field_id'        => $this->getFieldId('Test\SourceEntity', 'rev_rel_mtm', 'manyToMany'),
                'owner'           => false,
                'target_entity'   => 'Test\SourceEntity',
                'target_field_id' => $this->getFieldId('Test\SourceEntity', 'rel_mtm', 'manyToMany')
            ]
        ];

        $selfConfig = $this->addEntityConfig(
            [
                'is_extend' => true,
                'state'     => ExtendScope::STATE_ACTIVE,
                'relation'  => $selfRelations,
            ],
            'Test\SourceEntity'
        );

        $this->addConfigNewField(
            [
                'target_entity' => 'Test\SourceEntity',
                'relation_key'  => 'manyToMany|Test\SourceEntity|Test\SourceEntity|rel_mtm'
            ],
            'manyToMany',
            'Test\SourceEntity',
            'rel_mtm'
        );
        $this->addConfigNewField(
            [
                'target_entity' => 'Test\SourceEntity',
                'relation_key'  => 'manyToMany|Test\SourceEntity|Test\SourceEntity|rel_mtm|inverse'
            ],
            'manyToMany',
            'Test\SourceEntity',
            'rev_rel_mtm'
        );
        if ($reverseElements) {
            $this->configs['Test\SourceEntity'] = array_reverse($this->configs['Test\SourceEntity']);
        }

        $this->extension->preUpdate();

        $this->assertEquals($expectedSelfRelations, $selfConfig->get('relation'));
    }

    /**
     * @dataProvider elementsOrderProvider
     */
    public function testCompleteSelfRelationForOneToMany($reverseElements)
    {
        $expectedSelfRelations = [
            'oneToMany|Test\SourceEntity|Test\TargetEntity|rel_otm' => [
                'field_id'        => $this->getFieldId('Test\SourceEntity', 'rel_otm', 'oneToMany'),
                'owner'           => false,
                'target_entity'   => 'Test\TargetEntity',
                'target_field_id' => $this->getFieldId('Test\TargetEntity', 'rev_rel_otm', 'manyToOne')
            ]
        ];
        $selfRelations = [
            'oneToMany|Test\SourceEntity|Test\TargetEntity|rel_otm' => [
                'target_field_id' => $this->getFieldId('Test\TargetEntity', 'rev_rel_otm', 'manyToOne')
            ]
        ];
        $targetRelations = [
            'oneToMany|Test\SourceEntity|Test\TargetEntity|rel_otm' => [
                'field_id'        => $this->getFieldId('Test\TargetEntity', 'rev_rel_otm', 'manyToOne'),
                'owner'           => true,
                'target_entity'   => 'Test\SourceEntity',
                'target_field_id' => $this->getFieldId('Test\SourceEntity', 'rel_otm', 'oneToMany')
            ]
        ];

        $selfConfig = $this->addEntityConfig(
            [
                'is_extend' => true,
                'state'     => ExtendScope::STATE_ACTIVE,
                'relation'  => $selfRelations,
            ],
            'Test\SourceEntity'
        );
        $targetConfig = $this->addEntityConfig(
            [
                'is_extend' => true,
                'state'     => ExtendScope::STATE_ACTIVE,
                'relation'  => $targetRelations,
            ],
            'Test\TargetEntity'
        );
        if ($reverseElements) {
            $this->configs[null] = array_reverse($this->configs[null]);
        }

        $this->addConfigNewField(
            [
                'target_entity' => 'Test\TargetEntity',
                'relation_key'  => 'oneToMany|Test\SourceEntity|Test\TargetEntity|rel_otm'
            ],
            'oneToMany',
            'Test\SourceEntity',
            'rel_otm'
        );
        $this->addConfigNewField(
            [
                'target_entity' => 'Test\SourceEntity',
                'relation_key'  => 'oneToMany|Test\SourceEntity|Test\TargetEntity|rel_otm'
            ],
            'manyToOne',
            'Test\TargetEntity',
            'rev_rel_otm'
        );

        $this->extension->preUpdate();

        $this->assertEquals($expectedSelfRelations, $selfConfig->get('relation'));
        $this->assertEquals($targetRelations, $targetConfig->get('relation'));
    }

    /**
     * @dataProvider elementsOrderProvider
     */
    public function testCompleteSelfRelationForOneToManyAndSameOwningAndTarget($reverseElements)
    {
        $expectedSelfRelations = [
            'oneToMany|Test\SourceEntity|Test\SourceEntity|rel_otm' => [
                'field_id'        => $this->getFieldId('Test\SourceEntity', 'rel_otm', 'oneToMany'),
                'owner'           => false,
                'target_entity'   => 'Test\SourceEntity',
                'target_field_id' => $this->getFieldId('Test\SourceEntity', 'rev_rel_otm', 'manyToOne')
            ],
            'oneToMany|Test\SourceEntity|Test\SourceEntity|rel_otm|inverse' => [
                'field_id'        => $this->getFieldId('Test\SourceEntity', 'rev_rel_otm', 'manyToOne'),
                'owner'           => true,
                'target_entity'   => 'Test\SourceEntity',
                'target_field_id' => $this->getFieldId('Test\SourceEntity', 'rel_otm', 'oneToMany')
            ]
        ];
        $selfRelations = [
            'oneToMany|Test\SourceEntity|Test\SourceEntity|rel_otm' => [
                'target_field_id' => $this->getFieldId('Test\SourceEntity', 'rev_rel_otm', 'manyToOne')
            ],
            'oneToMany|Test\SourceEntity|Test\SourceEntity|rel_otm|inverse' => [
                'field_id'        => $this->getFieldId('Test\SourceEntity', 'rev_rel_otm', 'manyToOne'),
                'owner'           => true,
                'target_entity'   => 'Test\SourceEntity',
                'target_field_id' => $this->getFieldId('Test\SourceEntity', 'rel_otm', 'oneToMany')
            ]
        ];

        $selfConfig = $this->addEntityConfig(
            [
                'is_extend' => true,
                'state'     => ExtendScope::STATE_ACTIVE,
                'relation'  => $selfRelations,
            ],
            'Test\SourceEntity'
        );

        $this->addConfigNewField(
            [
                'target_entity' => 'Test\SourceEntity',
                'relation_key'  => 'oneToMany|Test\SourceEntity|Test\SourceEntity|rel_otm'
            ],
            'oneToMany',
            'Test\SourceEntity',
            'rel_otm'
        );
        $this->addConfigNewField(
            [
                'target_entity' => 'Test\SourceEntity',
                'relation_key'  => 'oneToMany|Test\SourceEntity|Test\SourceEntity|rel_otm|inverse'
            ],
            'manyToOne',
            'Test\SourceEntity',
            'rev_rel_otm'
        );
        if ($reverseElements) {
            $this->configs['Test\SourceEntity'] = array_reverse($this->configs['Test\SourceEntity']);
        }

        $this->extension->preUpdate();

        $this->assertEquals($expectedSelfRelations, $selfConfig->get('relation'));
    }

    /**
     * @param array  $values
     * @param string $type
     * @param string $className
     * @param string $fieldName
     *
     * @return Config
     */
    protected function addConfigNewField(
        $values = [],
        $type = 'string',
        $className = 'Test\SourceEntity',
        $fieldName = 'testField'
    ) {
        $resultValues = [
            'owner'      => ExtendScope::OWNER_CUSTOM,
            'state'      => ExtendScope::STATE_NEW,
            'is_deleted' => false,
        ];

        if (!empty($values)) {
            $resultValues = array_merge($resultValues, $values);
        }

        $config = new Config(new FieldConfigId('extend', $className, $fieldName, $type));
        $config->setValues($resultValues);

        $this->configs[$className][] = $config;

        return $config;
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

        $config = new Config(new EntityConfigId('extend', $className));
        $config->setValues($resultValues);

        $this->configs[null][] = $config;

        return $config;
    }

    /**
     * @param string $className
     * @param string $fieldName
     * @param string $fieldType
     *
     * @return FieldConfigId
     */
    protected function getFieldId($className, $fieldName, $fieldType)
    {
        return new FieldConfigId('extend', $className, $fieldName, $fieldType);
    }

    /**
     * @return array
     */
    public function elementsOrderProvider()
    {
        return [
            ['reverseElements' => false],
            ['reverseElements' => true],
        ];
    }
}
