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

    public function testManyToOneNoChangesIfReverseRelationAlreadyCreated()
    {
        $selfRelations   = [
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
        $this->addConfigNewField(
            [
                'target_entity' => 'Test\TargetEntity',
                'relation_key'  => 'manyToOne|Test\SourceEntity|Test\TargetEntity|rel_mto'
            ],
            'manyToOne',
            'Test\SourceEntity',
            'rel_mto'
        );

        $targetConfig = $this->addEntityConfig(
            [
                'is_extend' => true,
                'state'     => ExtendScope::STATE_ACTIVE,
                'relation'  => $targetRelations,
            ],
            'Test\TargetEntity'
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

        // assert nothing changed
        $this->assertEquals($selfRelations, $selfConfig->get('relation'));
        $this->assertEquals($targetRelations, $targetConfig->get('relation'));
    }

    public function testManyToManyNoChangesIfReverseRelationAlreadyCreated()
    {
        $selfRelations   = [
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
        $this->addConfigNewField(
            [
                'target_entity' => 'Test\TargetEntity',
                'relation_key'  => 'manyToMany|Test\SourceEntity|Test\TargetEntity|rel_mtm'
            ],
            'manyToMany',
            'Test\SourceEntity',
            'rel_mtm'
        );

        $targetConfig = $this->addEntityConfig(
            [
                'is_extend' => true,
                'state'     => ExtendScope::STATE_ACTIVE,
                'relation'  => $targetRelations,
            ],
            'Test\TargetEntity'
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

        // assert nothing changed
        $this->assertEquals($selfRelations, $selfConfig->get('relation'));
        $this->assertEquals($targetRelations, $targetConfig->get('relation'));
    }

    public function testOneToManyNoChangesIfReverseRelationAlreadyCreated()
    {
        $selfRelations   = [
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
        $this->addConfigNewField(
            [
                'target_entity' => 'Test\TargetEntity',
                'relation_key'  => 'oneToMany|Test\SourceEntity|Test\TargetEntity|rel_otm'
            ],
            'oneToMany',
            'Test\SourceEntity',
            'rel_otm'
        );

        $targetConfig = $this->addEntityConfig(
            [
                'is_extend' => true,
                'state'     => ExtendScope::STATE_ACTIVE,
                'relation'  => $targetRelations,
            ],
            'Test\TargetEntity'
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

        // assert nothing changed
        $this->assertEquals($selfRelations, $selfConfig->get('relation'));
        $this->assertEquals($targetRelations, $targetConfig->get('relation'));
    }

    public function testManyToOneWhenNoReverseRelation()
    {
        $selfRelations   = [
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
        $this->addConfigNewField(
            [
                'target_entity' => 'Test\TargetEntity',
                'relation_key'  => 'manyToOne|Test\SourceEntity|Test\TargetEntity|rel_mto'
            ],
            'manyToOne',
            'Test\SourceEntity',
            'rel_mto'
        );

        $targetConfig = $this->addEntityConfig(
            [
                'is_extend' => true,
                'state'     => ExtendScope::STATE_ACTIVE,
                'relation'  => $targetRelations,
            ],
            'Test\TargetEntity'
        );

        $this->extension->preUpdate();

        // assert nothing changed
        $this->assertEquals($selfRelations, $selfConfig->get('relation'));
        $this->assertEquals($targetRelations, $targetConfig->get('relation'));
    }

    public function testManyToManyWhenNoReverseRelation()
    {
        $selfRelations   = [
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
        $this->addConfigNewField(
            [
                'target_entity' => 'Test\TargetEntity',
                'relation_key'  => 'manyToMany|Test\SourceEntity|Test\TargetEntity|rel_mtm'
            ],
            'manyToMany',
            'Test\SourceEntity',
            'rel_mtm'
        );

        $targetConfig = $this->addEntityConfig(
            [
                'is_extend' => true,
                'state'     => ExtendScope::STATE_ACTIVE,
                'relation'  => $targetRelations,
            ],
            'Test\TargetEntity'
        );

        $this->extension->preUpdate();

        // assert nothing changed
        $this->assertEquals($selfRelations, $selfConfig->get('relation'));
        $this->assertEquals($targetRelations, $targetConfig->get('relation'));
    }

    public function testOneToManyWhenNoReverseRelation()
    {
        $selfRelations   = [
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
        $this->addConfigNewField(
            [
                'target_entity' => 'Test\TargetEntity',
                'relation_key'  => 'oneToMany|Test\SourceEntity|Test\TargetEntity|rel_otm'
            ],
            'oneToMany',
            'Test\SourceEntity',
            'rel_otm'
        );

        $targetConfig = $this->addEntityConfig(
            [
                'is_extend' => true,
                'state'     => ExtendScope::STATE_ACTIVE,
                'relation'  => $targetRelations,
            ],
            'Test\TargetEntity'
        );

        $this->extension->preUpdate();

        // assert nothing changed
        $this->assertEquals($selfRelations, $selfConfig->get('relation'));
        $this->assertEquals($targetRelations, $targetConfig->get('relation'));
    }

    public function testManyToOneReverseRelationToBeCreated()
    {
        $selfRelations   = [
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
        $this->addConfigNewField(
            [
                'target_entity' => 'Test\TargetEntity',
                'relation_key'  => 'manyToOne|Test\SourceEntity|Test\TargetEntity|rel_mto'
            ],
            'manyToOne',
            'Test\SourceEntity',
            'rel_mto'
        );

        $targetConfig = $this->addEntityConfig(
            [
                'is_extend' => true,
                'state'     => ExtendScope::STATE_ACTIVE,
                'relation'  => $targetRelations,
            ],
            'Test\TargetEntity'
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

        $relationKey = 'manyToOne|Test\SourceEntity|Test\TargetEntity|rel_mto';

        $selfRelations[$relationKey]['target_field_id'] =
            $this->getFieldId('Test\TargetEntity', 'rev_rel_mto', 'oneToMany');
        $targetRelations[$relationKey]['field_id']      =
            $this->getFieldId('Test\TargetEntity', 'rev_rel_mto', 'oneToMany');

        $this->assertEquals($selfRelations, $selfConfig->get('relation'));
        $this->assertEquals($targetRelations, $targetConfig->get('relation'));
    }

    public function testManyToOneWhenRelationToBeCreated()
    {
        $selfRelations   = [
            'manyToOne|Test\SourceEntity|Test\TargetEntity|rel_mto' => [
                'field_id'        => $this->getFieldId('Test\SourceEntity', 'rel_mto', 'manyToOne'),
                'owner'           => true,
                'target_entity'   => 'Test\TargetEntity',
                'target_field_id' => $this->getFieldId('Test\TargetEntity', 'rev_rel_mto', 'oneToMany'),
                'cascade'         => ['persist', 'remove']
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
        $this->addConfigNewField(
            [
                'target_entity' => 'Test\TargetEntity',
                'relation_key'  => 'manyToOne|Test\SourceEntity|Test\TargetEntity|rel_mto',
                'cascade'       => ['persist', 'remove']
            ],
            'manyToOne',
            'Test\SourceEntity',
            'rel_mto'
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

    public function testManyToOneWhenUnidirectionalRelationToBeCreated()
    {
        $selfRelations   = [
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
                'state'     => ExtendScope::STATE_ACTIVE
            ],
            'Test\SourceEntity'
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

        $targetConfig = $this->addEntityConfig(
            [
                'is_extend' => true,
                'state'     => ExtendScope::STATE_ACTIVE
            ],
            'Test\TargetEntity'
        );

        $this->extension->preUpdate();

        $this->assertEquals($selfRelations, $selfConfig->get('relation'));
        $this->assertEquals($targetRelations, $targetConfig->get('relation'));
    }

    public function testManyToManyWhenRelationToBeCreated()
    {
        $selfRelations   = [
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
        $this->addConfigNewField(
            [
                'target_entity' => 'Test\TargetEntity',
                'relation_key'  => 'manyToMany|Test\SourceEntity|Test\TargetEntity|rel_mtm',
                'cascade'       => ['persist', 'remove']
            ],
            'manyToMany',
            'Test\SourceEntity',
            'rel_mtm'
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

    public function testManyToManyWhenUnidirectionalRelationToBeCreated()
    {
        $selfRelations   = [
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
                'state'     => ExtendScope::STATE_ACTIVE
            ],
            'Test\SourceEntity'
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

        $targetConfig = $this->addEntityConfig(
            [
                'is_extend' => true,
                'state'     => ExtendScope::STATE_ACTIVE
            ],
            'Test\TargetEntity'
        );

        $this->extension->preUpdate();

        $this->assertEquals($selfRelations, $selfConfig->get('relation'));
        $this->assertEquals($targetRelations, $targetConfig->get('relation'));
    }

    public function testOneToManyWhenRelationToBeCreated()
    {
        $selfRelations   = [
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
        $this->addConfigNewField(
            [
                'target_entity' => 'Test\TargetEntity',
                'relation_key'  => 'oneToMany|Test\SourceEntity|Test\TargetEntity|rel_otm',
                'cascade'       => ['persist', 'remove']
            ],
            'oneToMany',
            'Test\SourceEntity',
            'rel_otm'
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

    public function testOneToManyWhenUnidirectionalRelationToBeCreated()
    {
        $selfRelations   = [
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
                'state'     => ExtendScope::STATE_ACTIVE
            ],
            'Test\SourceEntity'
        );
        $this->addConfigNewField(
            [
                'target_entity' => 'Test\TargetEntity',
                'relation_key'  => 'oneToMany|Test\SourceEntity|Test\TargetEntity|rel_otm'
            ],
            'oneToMany',
            'Test\SourceEntity',
            'rel_otm'
        );

        $targetConfig = $this->addEntityConfig(
            [
                'is_extend' => true,
                'state'     => ExtendScope::STATE_ACTIVE
            ],
            'Test\TargetEntity'
        );

        $this->extension->preUpdate();

        $this->assertEquals($selfRelations, $selfConfig->get('relation'));
        $this->assertEquals($targetRelations, $targetConfig->get('relation'));
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

        return $this->extendConfigProvider->addFieldConfig(
            $className,
            $fieldName,
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
}
