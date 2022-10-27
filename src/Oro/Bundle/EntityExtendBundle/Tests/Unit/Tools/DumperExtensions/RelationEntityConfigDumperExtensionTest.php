<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\Tools\DumperExtensions;

use Oro\Bundle\EntityConfigBundle\Config\Config;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Config\Id\EntityConfigId;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityConfigBundle\Exception\RuntimeException;
use Oro\Bundle\EntityExtendBundle\Configuration\EntityExtendConfigurationProvider;
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
    private $extension;

    /** @var array */
    private $configs = [];

    protected function setUp(): void
    {
        $configManager = $this->createMock(ConfigManager::class);

        $entityExtendConfigurationProvider = $this->createMock(EntityExtendConfigurationProvider::class);
        $entityExtendConfigurationProvider->expects(self::any())
            ->method('getUnderlyingTypes')
            ->willReturn(['enum' => 'manyToOne', 'multiEnum' => 'manyToMany']);

        $this->extension = new RelationEntityConfigDumperExtension(
            $configManager,
            new FieldTypeHelper($entityExtendConfigurationProvider)
        );

        // will be filled by addEntityConfig and addConfigNewField
        $this->configs = [];

        $configManager->expects($this->any())
            ->method('getConfigs')
            ->with('extend')
            ->willReturnCallback(function ($scope, $className) {
                return $this->configs[$className] ?? [];
            });
        $configManager->expects($this->any())
            ->method('hasConfig')
            ->willReturnCallback(function ($className, $fieldName) {
                return $this->hasConfig($className, $fieldName);
            });
        $configManager->expects($this->any())
            ->method('getEntityConfig')
            ->with('extend')
            ->willReturnCallback(function ($scope, $className) {
                return $this->getEntityConfig($className);
            });
        $configManager->expects($this->any())
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
     */
    public function testReverseRelationAlreadyCreatedForManyToOne(bool $reverseElements)
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
     */
    public function testReverseRelationAlreadyCreatedForManyToMany(bool $reverseElements)
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
     */
    public function testReverseRelationAlreadyCreatedForOneToMany(bool $reverseElements)
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
     */
    public function testNoReverseRelationForManyToOne(bool $reverseElements)
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
     */
    public function testNoReverseRelationForManyToMany(bool $reverseElements)
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
     */
    public function testNoReverseRelationForOneToMany(bool $reverseElements)
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
     */
    public function testSelfRelationShouldBeCreatedForManyToOne(bool $reverseElements)
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
     */
    public function testSelfRelationWithOptionsShouldBeCreatedForManyToOne(bool $reverseElements)
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
     */
    public function testSelfRelationShouldBeCreatedForManyToOneAndSameOwningAndTarget(bool $reverseElements)
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
     */
    public function testSelfRelationWithOptionsShouldBeCreatedForManyToOneAndSameOwningAndTarget(bool $reverseElements)
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
     */
    public function testSelfRelationShouldBeCreatedForManyToMany(bool $reverseElements)
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
     */
    public function testSelfRelationWithOptionsShouldBeCreatedForManyToMany(bool $reverseElements)
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
     */
    public function testSelfRelationShouldBeCreatedForManyToManyAndSameOwningAndTarget(bool $reverseElements)
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
     */
    public function testSelfRelationWithOptionsShouldBeCreatedForManyToManyAndSameOwningAndTarget(bool $reverseElements)
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
     */
    public function testSelfRelationShouldBeCreatedForOneToMany(bool $reverseElements)
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
     */
    public function testSelfRelationWithOptionsShouldBeCreatedForOneToMany(bool $reverseElements)
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
     */
    public function testSelfRelationShouldBeCreatedForOneToManyAndSameOwningAndTarget(bool $reverseElements)
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
     */
    public function testSelfRelationWithOptionsShouldBeCreatedForOneToManyAndSameOwningAndTarget(bool $reverseElements)
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
     */
    public function testCompleteRelationForManyToOne(bool $reverseElements)
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
     */
    public function testCompleteRelationWithOptionsForManyToOne(bool $reverseElements)
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
     */
    public function testCompleteRelationForManyToOneAndSameOwningAndTarget(bool $reverseElements)
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
     */
    public function testCompleteRelationWithOptionsForManyToOneAndSameOwningAndTarget(bool $reverseElements)
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
     */
    public function testCompleteRelationForManyToMany(bool $reverseElements)
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
     */
    public function testCompleteRelationWithOptionsForManyToMany(bool $reverseElements)
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
     */
    public function testCompleteRelationForManyToManyAndSameOwningAndTarget(bool $reverseElements)
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
     */
    public function testCompleteRelationWithOptionsForManyToManyAndSameOwningAndTarget(bool $reverseElements)
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
     */
    public function testCompleteRelationForOneToMany(bool $reverseElements)
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
     */
    public function testCompleteRelationWithOptionsForOneToMany(bool $reverseElements)
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
     */
    public function testCompleteRelationForOneToManyAndSameOwningAndTarget(bool $reverseElements)
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
     */
    public function testCompleteRelationWithOptionsForOneToManyAndSameOwningAndTarget(bool $reverseElements)
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

    private function addConfigNewField(array $values, string $type, string $className, string $fieldName): void
    {
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
    }

    private function addEntityConfig(array $values, string $className): Config
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

    private function hasConfig(string $className, string $fieldName): bool
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

    private function getEntityConfig(string $className): Config
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

    private function getFieldConfig(string $className, string $fieldName): Config
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

    private function getFieldId(string $className, string $fieldName, string $fieldType): FieldConfigId
    {
        return new FieldConfigId('extend', $className, $fieldName, $fieldType);
    }

    public function elementsOrderProvider(): array
    {
        return [
            ['reverseElements' => false],
            ['reverseElements' => true],
        ];
    }
}
