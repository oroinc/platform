<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\Tools\DumperExtensions;

use Oro\Bundle\EntityConfigBundle\Config\Config;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Config\Id\EntityConfigId;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityConfigBundle\Exception\RuntimeException;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\EntityExtendBundle\Extend\FieldTypeHelper;
use Oro\Bundle\EntityExtendBundle\Tools\DumperExtensions\RelationEntityConfigDumperExtension;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendConfigDumper;

/**
 * @SuppressWarnings(PHPMD.ExcessiveClassLength)
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.TooManyMethods)
 */
class RelationEntityConfigDumperExtensionTest extends \PHPUnit\Framework\TestCase
{
    /** @var RelationEntityConfigDumperExtension */
    protected $extension;

    /** @var ConfigManager|\PHPUnit\Framework\MockObject\MockObject */
    protected $configManager;

    /** @var array */
    protected $configs = [];

    public function setUp()
    {
        $this->configManager = $this->createMock(ConfigManager::class);

        $this->extension = new RelationEntityConfigDumperExtension(
            $this->configManager,
            new FieldTypeHelper(['enum' => 'manyToOne', 'multiEnum' => 'manyToMany'])
        );

        // will be filled by addEntityConfig and addConfigNewField
        $this->configs = [];

        $this->configManager->expects($this->any())
            ->method('getConfigs')
            ->with('extend')
            ->willReturnCallback(function ($scope, $className) {
                return $this->configs[$className] ?? [];
            });
        $this->configManager->expects($this->any())
            ->method('hasConfig')
            ->willReturnCallback(function ($className, $fieldName) {
                return $this->hasConfig($className, $fieldName);
            });
        $this->configManager->expects($this->any())
            ->method('getEntityConfig')
            ->with('extend')
            ->willReturnCallback(function ($scope, $className) {
                return $this->getEntityConfig($className);
            });
        $this->configManager->expects($this->any())
            ->method('getFieldConfig')
            ->with('extend')
            ->willReturnCallback(function ($scope, $className, $fieldName) {
                return $this->getFieldConfig($className, $fieldName);
            });
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
     * @param bool $reverseElements
     */
    public function testReverseRelationAlreadyCreatedForManyToOne($reverseElements)
    {
        $relationKey = 'manyToOne|Test\SourceEntity|Test\TargetEntity|rel_mto';
        $selfRelations = [
            $relationKey => [
                'field_id'        => $this->getFieldId('Test\SourceEntity', 'rel_mto', 'manyToOne'),
                'owner'           => true,
                'target_entity'   => 'Test\TargetEntity',
                'target_field_id' => $this->getFieldId('Test\TargetEntity', 'rev_rel_mto', 'oneToMany')
            ]
        ];
        $targetRelations = [
            $relationKey => [
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
                'relation_key'  => $relationKey
            ],
            'manyToOne',
            'Test\SourceEntity',
            'rel_mto'
        );
        $this->addConfigNewField(
            [
                'target_entity' => 'Test\SourceEntity',
                'relation_key'  => $relationKey
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
     * @param bool $reverseElements
     */
    public function testReverseRelationAlreadyCreatedForManyToMany($reverseElements)
    {
        $relationKey = 'manyToMany|Test\SourceEntity|Test\TargetEntity|rel_mtm';
        $selfRelations = [
            $relationKey => [
                'field_id'        => $this->getFieldId('Test\SourceEntity', 'rel_mtm', 'manyToMany'),
                'owner'           => true,
                'target_entity'   => 'Test\TargetEntity',
                'target_field_id' => $this->getFieldId('Test\TargetEntity', 'sourceentity_rel_mtm', 'manyToMany')
            ]
        ];
        $targetRelations = [
            $relationKey => [
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
                'relation_key'  => $relationKey
            ],
            'manyToMany',
            'Test\SourceEntity',
            'rel_mtm'
        );
        $this->addConfigNewField(
            [
                'target_entity' => 'Test\SourceEntity',
                'relation_key'  => $relationKey
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
     * @param bool $reverseElements
     */
    public function testReverseRelationAlreadyCreatedForOneToMany($reverseElements)
    {
        $relationKey = 'oneToMany|Test\SourceEntity|Test\TargetEntity|rel_otm';
        $selfRelations = [
            $relationKey => [
                'field_id'        => $this->getFieldId('Test\SourceEntity', 'rel_otm', 'oneToMany'),
                'owner'           => false,
                'target_entity'   => 'Test\TargetEntity',
                'target_field_id' => $this->getFieldId('Test\TargetEntity', 'sourceentity_rel_otm', 'manyToOne')
            ]
        ];
        $targetRelations = [
            $relationKey => [
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
                'relation_key'  => $relationKey
            ],
            'oneToMany',
            'Test\SourceEntity',
            'rel_otm'
        );
        $this->addConfigNewField(
            [
                'target_entity' => 'Test\SourceEntity',
                'relation_key'  => $relationKey
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
     * @param bool $reverseElements
     */
    public function testNoReverseRelationForManyToOne($reverseElements)
    {
        $relationKey = 'manyToOne|Test\SourceEntity|Test\TargetEntity|rel_mto';
        $selfRelations = [
            $relationKey => [
                'field_id'        => $this->getFieldId('Test\SourceEntity', 'rel_mto', 'manyToOne'),
                'owner'           => true,
                'target_entity'   => 'Test\TargetEntity',
                'target_field_id' => false
            ]
        ];
        $targetRelations = [
            $relationKey => [
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
                'relation_key'  => $relationKey
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
        $relationKey = 'manyToOne|Test\SourceEntity|Test\SourceEntity|rel_mto';
        $selfRelations = [
            $relationKey              => [
                'field_id'        => $this->getFieldId('Test\SourceEntity', 'rel_mto', 'manyToOne'),
                'owner'           => true,
                'target_entity'   => 'Test\SourceEntity',
                'target_field_id' => false
            ],
            $relationKey . '|inverse' => [
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
                'relation_key'  => $relationKey
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
     * @param bool $reverseElements
     */
    public function testNoReverseRelationForManyToMany($reverseElements)
    {
        $relationKey = 'manyToMany|Test\SourceEntity|Test\TargetEntity|rel_mtm';
        $selfRelations = [
            $relationKey => [
                'field_id'        => $this->getFieldId('Test\SourceEntity', 'rel_mtm', 'manyToMany'),
                'owner'           => true,
                'target_entity'   => 'Test\TargetEntity',
                'target_field_id' => $this->getFieldId('Test\TargetEntity', 'sourceentity_rel_mtm', 'manyToMany')
            ]
        ];
        $targetRelations = [
            $relationKey => [
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
                'relation_key'  => $relationKey
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
        $relationKey = 'manyToMany|Test\SourceEntity|Test\SourceEntity|rel_mtm';
        $selfRelations = [
            $relationKey              => [
                'field_id'        => $this->getFieldId('Test\SourceEntity', 'rel_mtm', 'manyToMany'),
                'owner'           => true,
                'target_entity'   => 'Test\SourceEntity',
                'target_field_id' => $this->getFieldId('Test\SourceEntity', 'sourceentity_rel_mtm', 'manyToMany')
            ],
            $relationKey . '|inverse' => [
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
                'relation_key'  => $relationKey
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
     * @param bool $reverseElements
     */
    public function testNoReverseRelationForOneToMany($reverseElements)
    {
        $relationKey = 'oneToMany|Test\SourceEntity|Test\TargetEntity|rel_otm';
        $selfRelations = [
            $relationKey => [
                'field_id'        => $this->getFieldId('Test\SourceEntity', 'rel_otm', 'oneToMany'),
                'owner'           => true,
                'target_entity'   => 'Test\TargetEntity',
                'target_field_id' => $this->getFieldId('Test\TargetEntity', 'sourceentity_rel_otm', 'manyToOne')
            ]
        ];
        $targetRelations = [
            $relationKey => [
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
                'relation_key'  => $relationKey
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
        $relationKey = 'oneToMany|Test\SourceEntity|Test\SourceEntity|rel_otm';
        $selfRelations = [
            $relationKey              => [
                'field_id'        => $this->getFieldId('Test\SourceEntity', 'rel_otm', 'oneToMany'),
                'owner'           => true,
                'target_entity'   => 'Test\SourceEntity',
                'target_field_id' => $this->getFieldId('Test\SourceEntity', 'sourceentity_rel_otm', 'manyToOne')
            ],
            $relationKey . '|inverse' => [
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
                'relation_key'  => $relationKey
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
     * @param bool $reverseElements
     */
    public function testSelfRelationShouldBeCreatedForManyToOne($reverseElements)
    {
        $relationKey = 'manyToOne|Test\SourceEntity|Test\TargetEntity|rel_mto';
        $expectedSelfRelations = [
            $relationKey => [
                'field_id'        => $this->getFieldId('Test\SourceEntity', 'rel_mto', 'manyToOne'),
                'owner'           => true,
                'target_entity'   => 'Test\TargetEntity',
                'target_field_id' => $this->getFieldId('Test\TargetEntity', 'rev_rel_mto', 'oneToMany')
            ]
        ];
        $targetRelations = [
            $relationKey => [
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
                'relation_key'  => $relationKey,
                'bidirectional' => true
            ],
            'manyToOne',
            'Test\SourceEntity',
            'rel_mto'
        );
        $this->addConfigNewField(
            [
                'target_entity' => 'Test\SourceEntity',
                'relation_key'  => $relationKey
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
     * @param bool $reverseElements
     */
    public function testSelfRelationWithOptionsShouldBeCreatedForManyToOne($reverseElements)
    {
        $relationKey = 'manyToOne|Test\SourceEntity|Test\TargetEntity|rel_mto';
        $expectedSelfRelations = [
            $relationKey => [
                'field_id'        => $this->getFieldId('Test\SourceEntity', 'rel_mto', 'manyToOne'),
                'owner'           => true,
                'target_entity'   => 'Test\TargetEntity',
                'target_field_id' => $this->getFieldId('Test\TargetEntity', 'rev_rel_mto', 'oneToMany'),
                'cascade'         => ['persist', 'remove'],
                'fetch'           => 'extra_lazy',
                'on_delete'       => 'CASCADE',
                'nullable'        => true
            ]
        ];
        $targetRelations = [
            $relationKey => [
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
                'relation_key'  => $relationKey,
                'bidirectional' => true,
                'cascade'       => ['persist', 'remove'],
                'fetch'         => 'extra_lazy',
                'on_delete'     => 'CASCADE',
                'nullable'      => true
            ],
            'manyToOne',
            'Test\SourceEntity',
            'rel_mto'
        );
        $this->addConfigNewField(
            [
                'target_entity' => 'Test\SourceEntity',
                'relation_key'  => $relationKey
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
     * @param bool $reverseElements
     */
    public function testSelfRelationShouldBeCreatedForManyToOneAndSameOwningAndTarget($reverseElements)
    {
        $relationKey = 'manyToOne|Test\SourceEntity|Test\SourceEntity|rel_mto';
        $selfRelations = [
            $relationKey . '|inverse' => [
                'field_id'        => $this->getFieldId('Test\SourceEntity', 'rev_rel_mto', 'oneToMany'),
                'owner'           => false,
                'target_entity'   => 'Test\SourceEntity',
                'target_field_id' => $this->getFieldId('Test\SourceEntity', 'rel_mto', 'manyToOne')
            ]
        ];
        $expectedSelfRelations = [
            $relationKey              => [
                'field_id'        => $this->getFieldId('Test\SourceEntity', 'rel_mto', 'manyToOne'),
                'owner'           => true,
                'target_entity'   => 'Test\SourceEntity',
                'target_field_id' => $this->getFieldId('Test\SourceEntity', 'rev_rel_mto', 'oneToMany')
            ],
            $relationKey . '|inverse' => [
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
                'relation_key'  => $relationKey,
                'bidirectional' => true
            ],
            'manyToOne',
            'Test\SourceEntity',
            'rel_mto'
        );
        $this->addConfigNewField(
            [
                'target_entity' => 'Test\SourceEntity',
                'relation_key'  => $relationKey . '|inverse'
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
     * @param bool $reverseElements
     */
    public function testSelfRelationWithOptionsShouldBeCreatedForManyToOneAndSameOwningAndTarget($reverseElements)
    {
        $relationKey = 'manyToOne|Test\SourceEntity|Test\SourceEntity|rel_mto';
        $selfRelations = [
            $relationKey . '|inverse' => [
                'field_id'        => $this->getFieldId('Test\SourceEntity', 'rev_rel_mto', 'oneToMany'),
                'owner'           => false,
                'target_entity'   => 'Test\SourceEntity',
                'target_field_id' => $this->getFieldId('Test\SourceEntity', 'rel_mto', 'manyToOne')
            ]
        ];
        $expectedSelfRelations = [
            $relationKey              => [
                'field_id'        => $this->getFieldId('Test\SourceEntity', 'rel_mto', 'manyToOne'),
                'owner'           => true,
                'target_entity'   => 'Test\SourceEntity',
                'target_field_id' => $this->getFieldId('Test\SourceEntity', 'rev_rel_mto', 'oneToMany'),
                'cascade'         => ['persist', 'remove'],
                'fetch'           => 'extra_lazy',
                'on_delete'       => 'CASCADE',
                'nullable'        => true
            ],
            $relationKey . '|inverse' => [
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
                'relation_key'  => $relationKey,
                'bidirectional' => true,
                'cascade'       => ['persist', 'remove'],
                'fetch'         => 'extra_lazy',
                'on_delete'     => 'CASCADE',
                'nullable'      => true,
            ],
            'manyToOne',
            'Test\SourceEntity',
            'rel_mto'
        );
        $this->addConfigNewField(
            [
                'target_entity' => 'Test\SourceEntity',
                'relation_key'  => $relationKey . '|inverse'
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
     * @param bool $reverseElements
     */
    public function testSelfRelationShouldBeCreatedForManyToMany($reverseElements)
    {
        $relationKey = 'manyToMany|Test\SourceEntity|Test\TargetEntity|rel_mtm';
        $expectedSelfRelations = [
            $relationKey => [
                'field_id'        => $this->getFieldId('Test\SourceEntity', 'rel_mtm', 'manyToMany'),
                'owner'           => true,
                'target_entity'   => 'Test\TargetEntity',
                'target_field_id' => $this->getFieldId('Test\TargetEntity', 'sourceentity_rel_mtm', 'manyToMany')
            ]
        ];
        $targetRelations = [
            $relationKey => [
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
                'relation_key'  => $relationKey,
                'bidirectional' => true
            ],
            'manyToMany',
            'Test\SourceEntity',
            'rel_mtm'
        );
        $this->addConfigNewField(
            [
                'target_entity' => 'Test\SourceEntity',
                'relation_key'  => $relationKey
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
     * @param bool $reverseElements
     */
    public function testSelfRelationWithOptionsShouldBeCreatedForManyToMany($reverseElements)
    {
        $relationKey = 'manyToMany|Test\SourceEntity|Test\TargetEntity|rel_mtm';
        $expectedSelfRelations = [
            $relationKey => [
                'field_id'        => $this->getFieldId('Test\SourceEntity', 'rel_mtm', 'manyToMany'),
                'owner'           => true,
                'target_entity'   => 'Test\TargetEntity',
                'target_field_id' => $this->getFieldId('Test\TargetEntity', 'sourceentity_rel_mtm', 'manyToMany'),
                'cascade'         => ['persist', 'remove'],
                'fetch'           => 'extra_lazy',
                'on_delete'       => 'CASCADE',
                'nullable'        => true
            ]
        ];
        $targetRelations = [
            $relationKey => [
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
                'relation_key'  => $relationKey,
                'bidirectional' => true,
                'cascade'       => ['persist', 'remove'],
                'fetch'         => 'extra_lazy',
                'on_delete'     => 'CASCADE',
                'nullable'      => true
            ],
            'manyToMany',
            'Test\SourceEntity',
            'rel_mtm'
        );
        $this->addConfigNewField(
            [
                'target_entity' => 'Test\SourceEntity',
                'relation_key'  => $relationKey
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
     * @param bool $reverseElements
     */
    public function testSelfRelationShouldBeCreatedForManyToManyAndSameOwningAndTarget($reverseElements)
    {
        $relationKey = 'manyToMany|Test\SourceEntity|Test\SourceEntity|rel_mtm';
        $expectedSelfRelations = [
            $relationKey              => [
                'field_id'        => $this->getFieldId('Test\SourceEntity', 'rel_mtm', 'manyToMany'),
                'owner'           => true,
                'target_entity'   => 'Test\SourceEntity',
                'target_field_id' => $this->getFieldId('Test\SourceEntity', 'sourceentity_rel_mtm', 'manyToMany')
            ],
            $relationKey . '|inverse' => [
                'field_id'        => $this->getFieldId('Test\SourceEntity', 'sourceentity_rel_mtm', 'manyToMany'),
                'owner'           => false,
                'target_entity'   => 'Test\SourceEntity',
                'target_field_id' => $this->getFieldId('Test\SourceEntity', 'rel_mtm', 'manyToMany')
            ]
        ];
        $selfRelations = [
            $relationKey . '|inverse' => [
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
                'relation_key'  => $relationKey,
                'bidirectional' => true
            ],
            'manyToMany',
            'Test\SourceEntity',
            'rel_mtm'
        );
        $this->addConfigNewField(
            [
                'target_entity' => 'Test\SourceEntity',
                'relation_key'  => $relationKey . '|inverse'
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
     * @param bool $reverseElements
     */
    public function testSelfRelationWithOptionsShouldBeCreatedForManyToManyAndSameOwningAndTarget($reverseElements)
    {
        $relationKey = 'manyToMany|Test\SourceEntity|Test\SourceEntity|rel_mtm';
        $expectedSelfRelations = [
            $relationKey              => [
                'field_id'        => $this->getFieldId('Test\SourceEntity', 'rel_mtm', 'manyToMany'),
                'owner'           => true,
                'target_entity'   => 'Test\SourceEntity',
                'target_field_id' => $this->getFieldId('Test\SourceEntity', 'sourceentity_rel_mtm', 'manyToMany'),
                'cascade'         => ['persist', 'remove'],
                'fetch'           => 'extra_lazy',
                'on_delete'       => 'CASCADE',
                'nullable'        => true
            ],
            $relationKey . '|inverse' => [
                'field_id'        => $this->getFieldId('Test\SourceEntity', 'sourceentity_rel_mtm', 'manyToMany'),
                'owner'           => false,
                'target_entity'   => 'Test\SourceEntity',
                'target_field_id' => $this->getFieldId('Test\SourceEntity', 'rel_mtm', 'manyToMany')
            ]
        ];
        $selfRelations = [
            $relationKey . '|inverse' => [
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
                'relation_key'  => $relationKey,
                'bidirectional' => true,
                'cascade'       => ['persist', 'remove'],
                'fetch'         => 'extra_lazy',
                'on_delete'     => 'CASCADE',
                'nullable'      => true
            ],
            'manyToMany',
            'Test\SourceEntity',
            'rel_mtm'
        );
        $this->addConfigNewField(
            [
                'target_entity' => 'Test\SourceEntity',
                'relation_key'  => $relationKey . '|inverse'
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
     * @param bool $reverseElements
     */
    public function testSelfRelationShouldBeCreatedForOneToMany($reverseElements)
    {
        $relationKey = 'oneToMany|Test\SourceEntity|Test\TargetEntity|rel_otm';
        $expectedSelfRelations = [
            $relationKey => [
                'field_id'        => $this->getFieldId('Test\SourceEntity', 'rel_otm', 'oneToMany'),
                'owner'           => false,
                'target_entity'   => 'Test\TargetEntity',
                'target_field_id' => $this->getFieldId('Test\TargetEntity', 'sourceentity_rel_otm', 'manyToOne')
            ]
        ];
        $targetRelations = [
            $relationKey => [
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
                'relation_key'  => $relationKey,
                'bidirectional' => true
            ],
            'oneToMany',
            'Test\SourceEntity',
            'rel_otm'
        );
        $this->addConfigNewField(
            [
                'target_entity' => 'Test\SourceEntity',
                'relation_key'  => $relationKey
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
     * @param bool $reverseElements
     */
    public function testSelfRelationWithOptionsShouldBeCreatedForOneToMany($reverseElements)
    {
        $relationKey = 'oneToMany|Test\SourceEntity|Test\TargetEntity|rel_otm';
        $expectedSelfRelations = [
            $relationKey => [
                'field_id'        => $this->getFieldId('Test\SourceEntity', 'rel_otm', 'oneToMany'),
                'owner'           => false,
                'target_entity'   => 'Test\TargetEntity',
                'target_field_id' => $this->getFieldId('Test\TargetEntity', 'sourceentity_rel_otm', 'manyToOne'),
                'cascade'         => ['persist', 'remove'],
                'fetch'           => 'extra_lazy',
                'on_delete'       => 'CASCADE',
                'nullable'        => true
            ]
        ];
        $targetRelations = [
            $relationKey => [
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
                'relation_key'  => $relationKey,
                'bidirectional' => true,
                'cascade'       => ['persist', 'remove'],
                'fetch'         => 'extra_lazy',
                'on_delete'     => 'CASCADE',
                'nullable'      => true
            ],
            'oneToMany',
            'Test\SourceEntity',
            'rel_otm'
        );
        $this->addConfigNewField(
            [
                'target_entity' => 'Test\SourceEntity',
                'relation_key'  => $relationKey
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
     * @param bool $reverseElements
     */
    public function testSelfRelationShouldBeCreatedForOneToManyAndSameOwningAndTarget($reverseElements)
    {
        $relationKey = 'oneToMany|Test\SourceEntity|Test\SourceEntity|rel_otm';
        $expectedSelfRelations = [
            $relationKey              => [
                'field_id'        => $this->getFieldId('Test\SourceEntity', 'rel_otm', 'oneToMany'),
                'owner'           => false,
                'target_entity'   => 'Test\SourceEntity',
                'target_field_id' => $this->getFieldId('Test\SourceEntity', 'sourceentity_rel_otm', 'manyToOne')
            ],
            $relationKey . '|inverse' => [
                'field_id'        => $this->getFieldId('Test\SourceEntity', 'sourceentity_rel_otm', 'manyToOne'),
                'owner'           => true,
                'target_entity'   => 'Test\SourceEntity',
                'target_field_id' => $this->getFieldId('Test\SourceEntity', 'rel_otm', 'oneToMany')
            ]
        ];
        $selfRelations = [
            $relationKey . '|inverse' => [
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
                'relation_key'  => $relationKey,
                'bidirectional' => true
            ],
            'oneToMany',
            'Test\SourceEntity',
            'rel_otm'
        );
        $this->addConfigNewField(
            [
                'target_entity' => 'Test\SourceEntity',
                'relation_key'  => $relationKey . '|inverse'
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

    /**
     * @dataProvider elementsOrderProvider
     * @param bool $reverseElements
     */
    public function testSelfRelationWithOptionsShouldBeCreatedForOneToManyAndSameOwningAndTarget($reverseElements)
    {
        $relationKey = 'oneToMany|Test\SourceEntity|Test\SourceEntity|rel_otm';
        $expectedSelfRelations = [
            $relationKey              => [
                'field_id'        => $this->getFieldId('Test\SourceEntity', 'rel_otm', 'oneToMany'),
                'owner'           => false,
                'target_entity'   => 'Test\SourceEntity',
                'target_field_id' => $this->getFieldId('Test\SourceEntity', 'sourceentity_rel_otm', 'manyToOne'),
                'cascade'         => ['persist', 'remove'],
                'fetch'           => 'extra_lazy',
                'on_delete'       => 'CASCADE',
                'nullable'        => true
            ],
            $relationKey . '|inverse' => [
                'field_id'        => $this->getFieldId('Test\SourceEntity', 'sourceentity_rel_otm', 'manyToOne'),
                'owner'           => true,
                'target_entity'   => 'Test\SourceEntity',
                'target_field_id' => $this->getFieldId('Test\SourceEntity', 'rel_otm', 'oneToMany')
            ]
        ];
        $selfRelations = [
            $relationKey . '|inverse' => [
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
                'relation_key'  => $relationKey,
                'bidirectional' => true,
                'cascade'       => ['persist', 'remove'],
                'fetch'         => 'extra_lazy',
                'on_delete'     => 'CASCADE',
                'nullable'      => true
            ],
            'oneToMany',
            'Test\SourceEntity',
            'rel_otm'
        );
        $this->addConfigNewField(
            [
                'target_entity' => 'Test\SourceEntity',
                'relation_key'  => $relationKey . '|inverse'
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
        $relationKey = 'manyToOne|Test\SourceEntity|Test\TargetEntity|rel_mto';
        $expectedSelfRelations = [
            $relationKey => [
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
                'relation_key'  => $relationKey
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
        $relationKey = 'manyToOne|Test\SourceEntity|Test\SourceEntity|rel_mto';
        $expectedSelfRelations = [
            $relationKey => [
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
                'relation_key'  => $relationKey
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
        $relationKey = 'manyToMany|Test\SourceEntity|Test\TargetEntity|rel_mtm';
        $expectedSelfRelations = [
            $relationKey => [
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
                'relation_key'  => $relationKey
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
        $relationKey = 'manyToMany|Test\SourceEntity|Test\SourceEntity|rel_mtm';
        $expectedSelfRelations = [
            $relationKey => [
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
                'relation_key'  => $relationKey
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
     * @param bool $reverseElements
     */
    public function testCompleteRelationForManyToOne($reverseElements)
    {
        $relationKey = 'manyToOne|Test\SourceEntity|Test\TargetEntity|rel_mto';
        $expectedSelfRelations = [
            $relationKey => [
                'field_id'        => $this->getFieldId('Test\SourceEntity', 'rel_mto', 'manyToOne'),
                'owner'           => true,
                'target_entity'   => 'Test\TargetEntity',
                'target_field_id' => $this->getFieldId('Test\TargetEntity', 'rev_rel_mto', 'oneToMany')
            ]
        ];
        $selfRelations = [
            $relationKey => [
                'target_field_id' => $this->getFieldId('Test\TargetEntity', 'rev_rel_mto', 'oneToMany')
            ]
        ];
        $targetRelations = [
            $relationKey => [
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
                'relation_key'  => $relationKey
            ],
            'manyToOne',
            'Test\SourceEntity',
            'rel_mto'
        );
        $this->addConfigNewField(
            [
                'target_entity' => 'Test\SourceEntity',
                'relation_key'  => $relationKey
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
     * @param bool $reverseElements
     */
    public function testCompleteRelationWithOptionsForManyToOne($reverseElements)
    {
        $relationKey = 'manyToOne|Test\SourceEntity|Test\TargetEntity|rel_mto';
        $expectedSelfRelations = [
            $relationKey => [
                'field_id'        => $this->getFieldId('Test\SourceEntity', 'rel_mto', 'manyToOne'),
                'owner'           => true,
                'target_entity'   => 'Test\TargetEntity',
                'target_field_id' => $this->getFieldId('Test\TargetEntity', 'rev_rel_mto', 'oneToMany'),
                'cascade'         => ['persist'],
                'fetch'           => 'extra_lazy',
                'on_delete'       => 'CASCADE',
                'nullable'        => true
            ]
        ];
        $expectedTargetRelations = [
            $relationKey => [
                'field_id'        => $this->getFieldId('Test\TargetEntity', 'rev_rel_mto', 'oneToMany'),
                'owner'           => false,
                'target_entity'   => 'Test\SourceEntity',
                'target_field_id' => $this->getFieldId('Test\SourceEntity', 'rel_mto', 'manyToOne'),
                'cascade'         => ['remove'],
                'fetch'           => 'extra_lazy',
                'on_delete'       => 'SET NULL',
                'nullable'        => false
            ]
        ];
        $selfRelations = [
            $relationKey => [
                'target_field_id' => $this->getFieldId('Test\TargetEntity', 'rev_rel_mto', 'oneToMany')
            ]
        ];
        $targetRelations = [
            $relationKey => [
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
                'relation_key'  => $relationKey,
                'cascade'       => ['persist'],
                'fetch'         => 'extra_lazy',
                'on_delete'     => 'CASCADE',
                'nullable'      => true
            ],
            'manyToOne',
            'Test\SourceEntity',
            'rel_mto'
        );
        $this->addConfigNewField(
            [
                'target_entity' => 'Test\SourceEntity',
                'relation_key'  => $relationKey,
                'cascade'       => ['remove'],
                'fetch'         => 'extra_lazy',
                'on_delete'     => 'SET NULL',
                'nullable'      => false
            ],
            'oneToMany',
            'Test\TargetEntity',
            'rev_rel_mto'
        );

        $this->extension->preUpdate();

        $this->assertEquals($expectedSelfRelations, $selfConfig->get('relation'));
        $this->assertEquals($expectedTargetRelations, $targetConfig->get('relation'));
    }

    /**
     * @dataProvider elementsOrderProvider
     * @param bool $reverseElements
     */
    public function testCompleteRelationForManyToOneAndSameOwningAndTarget($reverseElements)
    {
        $relationKey = 'manyToOne|Test\SourceEntity|Test\SourceEntity|rel_mto';
        $expectedSelfRelations = [
            $relationKey              => [
                'field_id'        => $this->getFieldId('Test\SourceEntity', 'rel_mto', 'manyToOne'),
                'owner'           => true,
                'target_entity'   => 'Test\SourceEntity',
                'target_field_id' => $this->getFieldId('Test\SourceEntity', 'rev_rel_mto', 'oneToMany')
            ],
            $relationKey . '|inverse' => [
                'field_id'        => $this->getFieldId('Test\SourceEntity', 'rev_rel_mto', 'oneToMany'),
                'owner'           => false,
                'target_entity'   => 'Test\SourceEntity',
                'target_field_id' => $this->getFieldId('Test\SourceEntity', 'rel_mto', 'manyToOne')
            ]
        ];
        $selfRelations = [
            $relationKey              => [
                'target_field_id' => $this->getFieldId('Test\SourceEntity', 'rev_rel_mto', 'oneToMany')
            ],
            $relationKey . '|inverse' => [
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
                'relation_key'  => $relationKey
            ],
            'manyToOne',
            'Test\SourceEntity',
            'rel_mto'
        );
        $this->addConfigNewField(
            [
                'target_entity' => 'Test\SourceEntity',
                'relation_key'  => $relationKey . '|inverse'
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
     * @param bool $reverseElements
     */
    public function testCompleteRelationWithOptionsForManyToOneAndSameOwningAndTarget($reverseElements)
    {
        $relationKey = 'manyToOne|Test\SourceEntity|Test\SourceEntity|rel_mto';
        $expectedSelfRelations = [
            $relationKey              => [
                'field_id'        => $this->getFieldId('Test\SourceEntity', 'rel_mto', 'manyToOne'),
                'owner'           => true,
                'target_entity'   => 'Test\SourceEntity',
                'target_field_id' => $this->getFieldId('Test\SourceEntity', 'rev_rel_mto', 'oneToMany'),
                'cascade'         => ['persist'],
                'fetch'           => 'extra_lazy',
                'on_delete'       => 'CASCADE',
                'nullable'        => true
            ],
            $relationKey . '|inverse' => [
                'field_id'        => $this->getFieldId('Test\SourceEntity', 'rev_rel_mto', 'oneToMany'),
                'owner'           => false,
                'target_entity'   => 'Test\SourceEntity',
                'target_field_id' => $this->getFieldId('Test\SourceEntity', 'rel_mto', 'manyToOne'),
                'cascade'         => ['remove'],
                'fetch'           => 'extra_lazy',
                'on_delete'       => 'SET NULL',
                'nullable'        => false
            ]
        ];
        $selfRelations = [
            $relationKey              => [
                'target_field_id' => $this->getFieldId('Test\SourceEntity', 'rev_rel_mto', 'oneToMany')
            ],
            $relationKey . '|inverse' => [
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
                'relation_key'  => $relationKey,
                'cascade'       => ['persist'],
                'fetch'         => 'extra_lazy',
                'on_delete'     => 'CASCADE',
                'nullable'      => true
            ],
            'manyToOne',
            'Test\SourceEntity',
            'rel_mto'
        );
        $this->addConfigNewField(
            [
                'target_entity' => 'Test\SourceEntity',
                'relation_key'  => $relationKey . '|inverse',
                'cascade'       => ['remove'],
                'fetch'         => 'extra_lazy',
                'on_delete'     => 'SET NULL',
                'nullable'      => false
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
     * @param bool $reverseElements
     */
    public function testCompleteRelationForManyToMany($reverseElements)
    {
        $relationKey = 'manyToMany|Test\SourceEntity|Test\TargetEntity|rel_mtm';
        $expectedSelfRelations = [
            $relationKey => [
                'field_id'        => $this->getFieldId('Test\SourceEntity', 'rel_mtm', 'manyToMany'),
                'owner'           => true,
                'target_entity'   => 'Test\TargetEntity',
                'target_field_id' => $this->getFieldId('Test\TargetEntity', 'rev_rel_mtm', 'manyToMany')
            ]
        ];
        $selfRelations = [
            $relationKey => [
                'target_field_id' => $this->getFieldId('Test\TargetEntity', 'rev_rel_mtm', 'manyToMany')
            ]
        ];
        $targetRelations = [
            $relationKey => [
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
                'relation_key'  => $relationKey
            ],
            'manyToMany',
            'Test\SourceEntity',
            'rel_mtm'
        );
        $this->addConfigNewField(
            [
                'target_entity' => 'Test\SourceEntity',
                'relation_key'  => $relationKey
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
     * @param bool $reverseElements
     */
    public function testCompleteRelationWithOptionsForManyToMany($reverseElements)
    {
        $relationKey = 'manyToMany|Test\SourceEntity|Test\TargetEntity|rel_mtm';
        $expectedSelfRelations = [
            $relationKey => [
                'field_id'        => $this->getFieldId('Test\SourceEntity', 'rel_mtm', 'manyToMany'),
                'owner'           => true,
                'target_entity'   => 'Test\TargetEntity',
                'target_field_id' => $this->getFieldId('Test\TargetEntity', 'rev_rel_mtm', 'manyToMany'),
                'cascade'         => ['persist'],
                'fetch'           => 'extra_lazy',
                'on_delete'       => 'CASCADE',
                'nullable'        => true
            ]
        ];
        $expectedTargetRelations = [
            $relationKey => [
                'field_id'        => $this->getFieldId('Test\TargetEntity', 'rev_rel_mtm', 'manyToMany'),
                'owner'           => false,
                'target_entity'   => 'Test\SourceEntity',
                'target_field_id' => $this->getFieldId('Test\SourceEntity', 'rel_mtm', 'manyToMany'),
                'cascade'         => ['remove'],
                'fetch'           => 'extra_lazy',
                'on_delete'       => 'SET NULL',
                'nullable'        => false
            ]
        ];
        $selfRelations = [
            $relationKey => [
                'target_field_id' => $this->getFieldId('Test\TargetEntity', 'rev_rel_mtm', 'manyToMany')
            ]
        ];
        $targetRelations = [
            $relationKey => [
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
                'relation_key'  => $relationKey,
                'cascade'       => ['persist'],
                'fetch'         => 'extra_lazy',
                'on_delete'     => 'CASCADE',
                'nullable'      => true
            ],
            'manyToMany',
            'Test\SourceEntity',
            'rel_mtm'
        );
        $this->addConfigNewField(
            [
                'target_entity' => 'Test\SourceEntity',
                'relation_key'  => $relationKey,
                'cascade'       => ['remove'],
                'fetch'         => 'extra_lazy',
                'on_delete'     => 'SET NULL',
                'nullable'      => false
            ],
            'manyToMany',
            'Test\TargetEntity',
            'rev_rel_mtm'
        );

        $this->extension->preUpdate();

        $this->assertEquals($expectedSelfRelations, $selfConfig->get('relation'));
        $this->assertEquals($expectedTargetRelations, $targetConfig->get('relation'));
    }

    /**
     * @dataProvider elementsOrderProvider
     * @param bool $reverseElements
     */
    public function testCompleteRelationForManyToManyAndSameOwningAndTarget($reverseElements)
    {
        $relationKey = 'manyToMany|Test\SourceEntity|Test\SourceEntity|rel_mtm';
        $expectedSelfRelations = [
            $relationKey              => [
                'field_id'        => $this->getFieldId('Test\SourceEntity', 'rel_mtm', 'manyToMany'),
                'owner'           => true,
                'target_entity'   => 'Test\SourceEntity',
                'target_field_id' => $this->getFieldId('Test\SourceEntity', 'rev_rel_mtm', 'manyToMany')
            ],
            $relationKey . '|inverse' => [
                'field_id'        => $this->getFieldId('Test\SourceEntity', 'rev_rel_mtm', 'manyToMany'),
                'owner'           => false,
                'target_entity'   => 'Test\SourceEntity',
                'target_field_id' => $this->getFieldId('Test\SourceEntity', 'rel_mtm', 'manyToMany')
            ]
        ];
        $selfRelations = [
            $relationKey              => [
                'target_field_id' => $this->getFieldId('Test\SourceEntity', 'rev_rel_mtm', 'manyToMany')
            ],
            $relationKey . '|inverse' => [
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
                'relation_key'  => $relationKey
            ],
            'manyToMany',
            'Test\SourceEntity',
            'rel_mtm'
        );
        $this->addConfigNewField(
            [
                'target_entity' => 'Test\SourceEntity',
                'relation_key'  => $relationKey . '|inverse'
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
     * @param bool $reverseElements
     */
    public function testCompleteRelationWithOptionsForManyToManyAndSameOwningAndTarget($reverseElements)
    {
        $relationKey = 'manyToMany|Test\SourceEntity|Test\SourceEntity|rel_mtm';
        $expectedSelfRelations = [
            $relationKey              => [
                'field_id'        => $this->getFieldId('Test\SourceEntity', 'rel_mtm', 'manyToMany'),
                'owner'           => true,
                'target_entity'   => 'Test\SourceEntity',
                'target_field_id' => $this->getFieldId('Test\SourceEntity', 'rev_rel_mtm', 'manyToMany'),
                'cascade'         => ['persist'],
                'fetch'           => 'extra_lazy',
                'on_delete'       => 'CASCADE',
                'nullable'        => true
            ],
            $relationKey . '|inverse' => [
                'field_id'        => $this->getFieldId('Test\SourceEntity', 'rev_rel_mtm', 'manyToMany'),
                'owner'           => false,
                'target_entity'   => 'Test\SourceEntity',
                'target_field_id' => $this->getFieldId('Test\SourceEntity', 'rel_mtm', 'manyToMany'),
                'cascade'         => ['remove'],
                'fetch'           => 'extra_lazy',
                'on_delete'       => 'SET NULL',
                'nullable'        => false
            ]
        ];
        $selfRelations = [
            $relationKey              => [
                'target_field_id' => $this->getFieldId('Test\SourceEntity', 'rev_rel_mtm', 'manyToMany')
            ],
            $relationKey . '|inverse' => [
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
                'relation_key'  => $relationKey,
                'cascade'       => ['persist'],
                'fetch'         => 'extra_lazy',
                'on_delete'     => 'CASCADE',
                'nullable'      => true
            ],
            'manyToMany',
            'Test\SourceEntity',
            'rel_mtm'
        );
        $this->addConfigNewField(
            [
                'target_entity' => 'Test\SourceEntity',
                'relation_key'  => $relationKey . '|inverse',
                'cascade'       => ['remove'],
                'fetch'         => 'extra_lazy',
                'on_delete'     => 'SET NULL',
                'nullable'      => false
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
     * @param bool $reverseElements
     */
    public function testCompleteRelationForOneToMany($reverseElements)
    {
        $relationKey = 'oneToMany|Test\SourceEntity|Test\TargetEntity|rel_otm';
        $expectedSelfRelations = [
            $relationKey => [
                'field_id'        => $this->getFieldId('Test\SourceEntity', 'rel_otm', 'oneToMany'),
                'owner'           => false,
                'target_entity'   => 'Test\TargetEntity',
                'target_field_id' => $this->getFieldId('Test\TargetEntity', 'rev_rel_otm', 'manyToOne')
            ]
        ];
        $selfRelations = [
            $relationKey => [
                'target_field_id' => $this->getFieldId('Test\TargetEntity', 'rev_rel_otm', 'manyToOne')
            ]
        ];
        $targetRelations = [
            $relationKey => [
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
                'relation_key'  => $relationKey
            ],
            'oneToMany',
            'Test\SourceEntity',
            'rel_otm'
        );
        $this->addConfigNewField(
            [
                'target_entity' => 'Test\SourceEntity',
                'relation_key'  => $relationKey
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
     * @param bool $reverseElements
     */
    public function testCompleteRelationWithOptionsForOneToMany($reverseElements)
    {
        $relationKey = 'oneToMany|Test\SourceEntity|Test\TargetEntity|rel_otm';
        $expectedSelfRelations = [
            $relationKey => [
                'field_id'        => $this->getFieldId('Test\SourceEntity', 'rel_otm', 'oneToMany'),
                'owner'           => false,
                'target_entity'   => 'Test\TargetEntity',
                'target_field_id' => $this->getFieldId('Test\TargetEntity', 'rev_rel_otm', 'manyToOne'),
                'cascade'         => ['persist'],
                'fetch'           => 'extra_lazy',
                'on_delete'       => 'CASCADE',
                'nullable'        => true
            ]
        ];
        $expectedTargetRelations = [
            $relationKey => [
                'field_id'        => $this->getFieldId('Test\TargetEntity', 'rev_rel_otm', 'manyToOne'),
                'owner'           => true,
                'target_entity'   => 'Test\SourceEntity',
                'target_field_id' => $this->getFieldId('Test\SourceEntity', 'rel_otm', 'oneToMany'),
                'cascade'         => ['remove'],
                'fetch'           => 'extra_lazy',
                'on_delete'       => 'SET NULL',
                'nullable'        => false
            ]
        ];
        $selfRelations = [
            $relationKey => [
                'target_field_id' => $this->getFieldId('Test\TargetEntity', 'rev_rel_otm', 'manyToOne')
            ]
        ];
        $targetRelations = [
            $relationKey => [
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
                'relation_key'  => $relationKey,
                'cascade'       => ['persist'],
                'fetch'         => 'extra_lazy',
                'on_delete'     => 'CASCADE',
                'nullable'      => true
            ],
            'oneToMany',
            'Test\SourceEntity',
            'rel_otm'
        );
        $this->addConfigNewField(
            [
                'target_entity' => 'Test\SourceEntity',
                'relation_key'  => $relationKey,
                'cascade'       => ['remove'],
                'fetch'         => 'extra_lazy',
                'on_delete'     => 'SET NULL',
                'nullable'      => false
            ],
            'manyToOne',
            'Test\TargetEntity',
            'rev_rel_otm'
        );

        $this->extension->preUpdate();

        $this->assertEquals($expectedSelfRelations, $selfConfig->get('relation'));
        $this->assertEquals($expectedTargetRelations, $targetConfig->get('relation'));
    }

    /**
     * @dataProvider elementsOrderProvider
     * @param bool $reverseElements
     */
    public function testCompleteRelationForOneToManyAndSameOwningAndTarget($reverseElements)
    {
        $relationKey = 'oneToMany|Test\SourceEntity|Test\SourceEntity|rel_otm';
        $expectedSelfRelations = [
            $relationKey              => [
                'field_id'        => $this->getFieldId('Test\SourceEntity', 'rel_otm', 'oneToMany'),
                'owner'           => false,
                'target_entity'   => 'Test\SourceEntity',
                'target_field_id' => $this->getFieldId('Test\SourceEntity', 'rev_rel_otm', 'manyToOne')
            ],
            $relationKey . '|inverse' => [
                'field_id'        => $this->getFieldId('Test\SourceEntity', 'rev_rel_otm', 'manyToOne'),
                'owner'           => true,
                'target_entity'   => 'Test\SourceEntity',
                'target_field_id' => $this->getFieldId('Test\SourceEntity', 'rel_otm', 'oneToMany')
            ]
        ];
        $selfRelations = [
            $relationKey              => [
                'target_field_id' => $this->getFieldId('Test\SourceEntity', 'rev_rel_otm', 'manyToOne')
            ],
            $relationKey . '|inverse' => [
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
                'relation_key'  => $relationKey
            ],
            'oneToMany',
            'Test\SourceEntity',
            'rel_otm'
        );
        $this->addConfigNewField(
            [
                'target_entity' => 'Test\SourceEntity',
                'relation_key'  => $relationKey . '|inverse'
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
     * @dataProvider elementsOrderProvider
     * @param bool $reverseElements
     */
    public function testCompleteRelationWithOptionsForOneToManyAndSameOwningAndTarget($reverseElements)
    {
        $relationKey = 'oneToMany|Test\SourceEntity|Test\SourceEntity|rel_otm';
        $expectedSelfRelations = [
            $relationKey              => [
                'field_id'        => $this->getFieldId('Test\SourceEntity', 'rel_otm', 'oneToMany'),
                'owner'           => false,
                'target_entity'   => 'Test\SourceEntity',
                'target_field_id' => $this->getFieldId('Test\SourceEntity', 'rev_rel_otm', 'manyToOne'),
                'cascade'         => ['persist'],
                'fetch'           => 'extra_lazy',
                'on_delete'       => 'CASCADE',
                'nullable'        => true
            ],
            $relationKey . '|inverse' => [
                'field_id'        => $this->getFieldId('Test\SourceEntity', 'rev_rel_otm', 'manyToOne'),
                'owner'           => true,
                'target_entity'   => 'Test\SourceEntity',
                'target_field_id' => $this->getFieldId('Test\SourceEntity', 'rel_otm', 'oneToMany'),
                'cascade'         => ['remove'],
                'fetch'           => 'extra_lazy',
                'on_delete'       => 'SET NULL',
                'nullable'        => false
            ]
        ];
        $selfRelations = [
            $relationKey              => [
                'target_field_id' => $this->getFieldId('Test\SourceEntity', 'rev_rel_otm', 'manyToOne')
            ],
            $relationKey . '|inverse' => [
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
                'relation_key'  => $relationKey,
                'cascade'       => ['persist'],
                'fetch'         => 'extra_lazy',
                'on_delete'     => 'CASCADE',
                'nullable'      => true
            ],
            'oneToMany',
            'Test\SourceEntity',
            'rel_otm'
        );
        $this->addConfigNewField(
            [
                'target_entity' => 'Test\SourceEntity',
                'relation_key'  => $relationKey . '|inverse',
                'cascade'       => ['remove'],
                'fetch'         => 'extra_lazy',
                'on_delete'     => 'SET NULL',
                'nullable'      => false
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
        array $values = [],
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
    protected function addEntityConfig(array $values = [], $className = 'Test\SourceEntity')
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
     *
     * @return bool
     */
    protected function hasConfig($className, $fieldName)
    {
        if (!isset($this->configs[$className])) {
            return false;
        }

        if (!$fieldName) {
            return true;
        }

        /** @var Config $fieldConfig */
        foreach ($this->configs[$className] as $fieldConfig) {
            if ($fieldConfig->getId()->getFieldName() === $fieldName) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param string $className
     *
     * @return Config
     */
    protected function getEntityConfig($className)
    {
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

    /**
     * @param string $className
     * @param string $fieldName
     *
     * @return Config
     */
    protected function getFieldConfig($className, $fieldName)
    {
        if (!isset($this->configs[$className])) {
            throw new RuntimeException(sprintf('Entity "%s" is not configurable', $className));
        }

        /** @var Config $fieldConfig */
        foreach ($this->configs[$className] as $fieldConfig) {
            if ($fieldConfig->getId()->getFieldName() === $fieldName) {
                return $fieldConfig;
            }
        }

        throw new RuntimeException(sprintf('Field "%s::%s" is not configurable', $className, $fieldName));
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
