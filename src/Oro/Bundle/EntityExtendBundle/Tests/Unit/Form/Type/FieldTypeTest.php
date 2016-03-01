<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\Form\Type;

use Genemu\Bundle\FormBundle\Form\JQuery\Type\Select2Type;

use Symfony\Component\Form\Extension\Validator\Type\FormTypeValidatorExtension;
use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\Form\Test\TypeTestCase;
use Symfony\Component\Validator\ConstraintValidatorFactory;
use Symfony\Component\Validator\DefaultTranslator;
use Symfony\Component\Validator\Mapping\ClassMetadataFactory;
use Symfony\Component\Validator\Mapping\Loader\LoaderChain;
use Symfony\Component\Validator\Validator;

use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityConfigBundle\Tests\Unit\ConfigProviderMock;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\EntityExtendBundle\Form\Type\FieldType;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendDbIdentifierNameGenerator;
use Oro\Bundle\EntityExtendBundle\Provider\FieldTypeProvider;
use Oro\Bundle\FormBundle\Form\Extension\DataBlockExtension;
use Oro\Bundle\TranslationBundle\Form\Extension\TranslatableChoiceTypeExtension;
use Oro\Bundle\TranslationBundle\Translation\Translator;

class FieldTypeTest extends TypeTestCase
{
    const FIELDS_GROUP = 'oro.entity_extend.form.data_type_group.fields';
    const RELATIONS_GROUP = 'oro.entity_extend.form.data_type_group.relations';

    /** @var FieldType $type */
    protected $type;

    /** @var \PHPUnit_Framework_MockObject_MockObject|ConfigManager */
    protected $configManager;

    /** @var \PHPUnit_Framework_MockObject_MockObject|Translator */
    protected $translator;

    /** @var \PHPUnit_Framework_MockObject_MockObject|FieldTypeProvider */
    protected $fieldTypeProvider;

    /** @var array */
    protected $defaultFieldTypeChoices = [
        self::FIELDS_GROUP    => [
            'bigint'    => 'oro.entity_extend.form.data_type.bigint',
            'boolean'   => 'oro.entity_extend.form.data_type.boolean',
            'date'      => 'oro.entity_extend.form.data_type.date',
            'datetime'  => 'oro.entity_extend.form.data_type.datetime',
            'decimal'   => 'oro.entity_extend.form.data_type.decimal',
            'enum'      => 'oro.entity_extend.form.data_type.enum',
            'file'      => 'oro.entity_extend.form.data_type.file',
            'float'     => 'oro.entity_extend.form.data_type.float',
            'image'     => 'oro.entity_extend.form.data_type.image',
            'integer'   => 'oro.entity_extend.form.data_type.integer',
            'money'     => 'oro.entity_extend.form.data_type.money',
            'multiEnum' => 'oro.entity_extend.form.data_type.multiEnum',
            'percent'   => 'oro.entity_extend.form.data_type.percent',
            'smallint'  => 'oro.entity_extend.form.data_type.smallint',
            'string'    => 'oro.entity_extend.form.data_type.string',
            'text'      => 'oro.entity_extend.form.data_type.text',
        ],
        self::RELATIONS_GROUP => [
            'manyToMany' => 'oro.entity_extend.form.data_type.manyToMany',
            'manyToOne'  => 'oro.entity_extend.form.data_type.manyToOne',
            'oneToMany'  => 'oro.entity_extend.form.data_type.oneToMany',
        ],
    ];

    protected function setUp()
    {
        parent::setUp();

        $this->configManager = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Config\ConfigManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->translator = $this->getMockBuilder('Oro\Bundle\TranslationBundle\Translation\Translator')
            ->disableOriginalConstructor()
            ->getMock();
        $this->translator->expects($this->any())
            ->method('trans')
            ->willReturnCallback(
                function ($id, $parameters) {
                    if ($id === 'oro.entity_extend.form.data_type.inverse_relation') {
                        return strtr('Reuse "%field_name%" of %entity_name%', $parameters);
                    }

                    return $id;
                }
            );

        $this->fieldTypeProvider = $this->getMockBuilder('Oro\Bundle\EntityExtendBundle\Provider\FieldTypeProvider')
            ->disableOriginalConstructor()
            ->getMock();
        $this->fieldTypeProvider->expects($this->any())
            ->method('getSupportedFieldTypes')
            ->willReturn(array_keys($this->defaultFieldTypeChoices[self::FIELDS_GROUP]));
        $this->fieldTypeProvider->expects($this->any())
            ->method('getSupportedRelationTypes')
            ->willReturn(array_keys($this->defaultFieldTypeChoices[self::RELATIONS_GROUP]));

        $this->type = new FieldType(
            $this->configManager,
            $this->translator,
            new ExtendDbIdentifierNameGenerator(),
            $this->fieldTypeProvider
        );
    }

    protected function tearDown()
    {
        unset($this->type, $this->configManager, $this->translator, $this->fieldTypeProvider);
    }

    protected function getExtensions()
    {
        $validator = new Validator(
            new ClassMetadataFactory(new LoaderChain([])),
            new ConstraintValidatorFactory(),
            new DefaultTranslator()
        );

        $select2ChoiceType = new Select2Type('choice');

        return [
            new PreloadedExtension(
                [
                    $select2ChoiceType->getName() => $select2ChoiceType,
                ],
                [
                    'form'   => [
                        new DataBlockExtension(),
                        new FormTypeValidatorExtension($validator)
                    ],
                    'choice' => [
                        new TranslatableChoiceTypeExtension()
                    ]
                ]
            )
        ];
    }

    public function testName()
    {
        $this->assertEquals('oro_entity_extend_field_type', $this->type->getName());
    }

    public function testType()
    {
        $extendConfigProvider = new ConfigProviderMock($this->configManager, 'extend');
        $entityConfigProvider = new ConfigProviderMock($this->configManager, 'entity');
        $this->configManager->expects($this->any())
            ->method('getProvider')
            ->willReturnMap(
                [
                    ['extend', $extendConfigProvider],
                    ['entity', $entityConfigProvider]
                ]
            );

        $extendConfigProvider->addEntityConfig('Test\SourceEntity');

        $form = $this->factory->create($this->type, null, ['class_name' => 'Test\SourceEntity']);

        $this->assertSame(
            $this->defaultFieldTypeChoices,
            $form->offsetGet('type')->getConfig()->getOption('choices')
        );

        $form->submit(['fieldName' => 'name', 'type' => 'string']);
        $this->assertTrue($form->isSynchronized());
    }

    public function testTypeForSourceEntity()
    {
        $this->prepareRelations();

        $form = $this->factory->create($this->type, null, ['class_name' => 'Test\SourceEntity']);

        $this->assertSame(
            $this->defaultFieldTypeChoices,
            $form->offsetGet('type')->getConfig()->getOption('choices')
        );

        $form->submit(['fieldName' => 'field1', 'type' => 'string']);
        $this->assertTrue($form->isSynchronized());
    }

    public function testTypeForTargetEntity()
    {
        $this->prepareRelations();

        $form = $this->factory->create($this->type, null, ['class_name' => 'Test\TargetEntity']);

        $expectedChoices = $this->defaultFieldTypeChoices;

        $expectedChoices[self::RELATIONS_GROUP] = array_merge(
            $expectedChoices[self::RELATIONS_GROUP],
            [
                'manyToMany|Test\SourceEntity|Test\TargetEntity|rel_m_t_m||sourceentity_rel_m_t_m' =>
                    'Reuse "Rel Many-To-Many" of Source Entity',
                'manyToOne|Test\SourceEntity|Test\TargetEntity|rel_m_t_o||'                        =>
                    'Reuse "Rel Many-To-One" of Source Entity',
                'oneToMany|Test\SourceEntity|Test\TargetEntity|rel_o_t_m||sourceentity_rel_o_t_m'  =>
                    'Reuse "Rel One-To-Many" of Source Entity',
            ]
        );
        $this->assertSame(
            $expectedChoices,
            $form->offsetGet('type')->getConfig()->getOption('choices')
        );

        $form->submit(['fieldName' => 'field1', 'type' => 'string']);
        $this->assertTrue($form->isSynchronized());
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    protected function prepareRelations()
    {
        $extendConfigProvider = new ConfigProviderMock($this->configManager, 'extend');
        $entityConfigProvider = new ConfigProviderMock($this->configManager, 'entity');
        $this->configManager->expects($this->any())
            ->method('getProvider')
            ->willReturnMap(
                [
                    ['extend', $extendConfigProvider],
                    ['entity', $entityConfigProvider]
                ]
            );

        $selfRelations = [
            'manyToOne|Test\SourceEntity|Test\TargetEntity|rel_m_t_o'  => [
                'field_id'        => new FieldConfigId(
                    'extend',
                    'Test\SourceEntity',
                    'rel_m_t_o',
                    'manyToOne'
                ),
                'owner'           => true,
                'target_entity'   => 'Test\TargetEntity',
                'target_field_id' => false
            ],
            'oneToMany|Test\SourceEntity|Test\TargetEntity|rel_o_t_m'  => [
                'field_id'        => new FieldConfigId(
                    'extend',
                    'Test\SourceEntity',
                    'rel_o_t_m',
                    'oneToMany'
                ),
                'owner'           => false,
                'target_entity'   => 'Test\TargetEntity',
                'target_field_id' => new FieldConfigId(
                    'extend',
                    'Test\TargetEntity',
                    'sourceentity_rel_o_t_m',
                    'manyToOne'
                )
            ],
            'manyToMany|Test\SourceEntity|Test\TargetEntity|rel_m_t_m' => [
                'field_id'        => new FieldConfigId(
                    'extend',
                    'Test\SourceEntity',
                    'rel_m_t_m',
                    'manyToMany'
                ),
                'owner'           => true,
                'target_entity'   => 'Test\TargetEntity',
                'target_field_id' => new FieldConfigId(
                    'extend',
                    'Test\TargetEntity',
                    'sourceentity_rel_m_t_m',
                    'manyToMany'
                )
            ]
        ];

        $targetRelations = [
            'manyToOne|Test\SourceEntity|Test\TargetEntity|rel_m_t_o'  => [
                'field_id'        => false,
                'owner'           => false,
                'target_entity'   => 'Test\SourceEntity',
                'target_field_id' => new FieldConfigId(
                    'extend',
                    'Test\SourceEntity',
                    'rel_m_t_o',
                    'manyToOne'
                )
            ],
            'oneToMany|Test\SourceEntity|Test\TargetEntity|rel_o_t_m'  => [
                'field_id'        => new FieldConfigId(
                    'extend',
                    'Test\TargetEntity',
                    'sourceentity_rel_o_t_m',
                    'manyToOne'
                ),
                'owner'           => true,
                'target_entity'   => 'Test\SourceEntity',
                'target_field_id' => new FieldConfigId(
                    'extend',
                    'Test\SourceEntity',
                    'rel_o_t_m',
                    'oneToMany'
                )
            ],
            'manyToMany|Test\SourceEntity|Test\TargetEntity|rel_m_t_m' => [
                'field_id'        => new FieldConfigId(
                    'extend',
                    'Test\TargetEntity',
                    'sourceentity_rel_m_t_m',
                    'manyToMany'
                ),
                'owner'           => false,
                'target_entity'   => 'Test\SourceEntity',
                'target_field_id' => new FieldConfigId(
                    'extend',
                    'Test\SourceEntity',
                    'rel_m_t_m',
                    'manyToMany'
                )
            ]
        ];

        $extendConfigProvider->addEntityConfig('Test\SourceEntity', ['relation' => $selfRelations]);
        $entityConfigProvider->addEntityConfig('Test\SourceEntity', ['label' => 'Source Entity']);
        $extendConfigProvider->addFieldConfig(
            'Test\SourceEntity',
            'rel_m_t_o',
            'manyToOne',
            ['state' => ExtendScope::STATE_ACTIVE]
        );
        $entityConfigProvider->addFieldConfig(
            'Test\SourceEntity',
            'rel_m_t_o',
            'manyToOne',
            ['label' => 'Rel Many-To-One']
        );
        $extendConfigProvider->addFieldConfig(
            'Test\SourceEntity',
            'rel_o_t_m',
            'oneToMany',
            ['state' => ExtendScope::STATE_ACTIVE]
        );
        $entityConfigProvider->addFieldConfig(
            'Test\SourceEntity',
            'rel_o_t_m',
            'oneToMany',
            ['label' => 'Rel One-To-Many']
        );
        $extendConfigProvider->addFieldConfig(
            'Test\SourceEntity',
            'rel_m_t_m',
            'manyToMany',
            ['state' => ExtendScope::STATE_ACTIVE]
        );
        $entityConfigProvider->addFieldConfig(
            'Test\SourceEntity',
            'rel_m_t_m',
            'manyToMany',
            ['label' => 'Rel Many-To-Many']
        );

        $extendConfigProvider->addEntityConfig('Test\TargetEntity', ['relation' => $targetRelations]);
        $entityConfigProvider->addEntityConfig('Test\TargetEntity', ['label' => 'Target Entity']);
    }

    public function testTypeForSourceEntityWithAlreadyCreatedReverseRelations()
    {
        $this->prepareRelationsWithReverseRelations();

        $form = $this->factory->create($this->type, null, ['class_name' => 'Test\SourceEntity']);

        $this->assertSame(
            $this->defaultFieldTypeChoices,
            $form->offsetGet('type')->getConfig()->getOption('choices')
        );

        $form->submit(['fieldName' => 'field1', 'type' => 'string']);
        $this->assertTrue($form->isSynchronized());
    }

    public function testTypeForTargetEntityWithAlreadyCreatedReverseRelations()
    {
        $this->prepareRelationsWithReverseRelations();

        $form = $this->factory->create($this->type, null, ['class_name' => 'Test\TargetEntity']);

        $this->assertSame(
            $this->defaultFieldTypeChoices,
            $form->offsetGet('type')->getConfig()->getOption('choices')
        );

        $form->submit(['fieldName' => 'field1', 'type' => 'string']);
        $this->assertTrue($form->isSynchronized());
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    protected function prepareRelationsWithReverseRelations()
    {
        $extendConfigProvider = new ConfigProviderMock($this->configManager, 'extend');
        $entityConfigProvider = new ConfigProviderMock($this->configManager, 'entity');
        $this->configManager->expects($this->any())
            ->method('getProvider')
            ->willReturnMap(
                [
                    ['extend', $extendConfigProvider],
                    ['entity', $entityConfigProvider]
                ]
            );

        $selfRelations = [
            'manyToOne|Test\SourceEntity|Test\TargetEntity|rel_m_t_o'  => [
                'field_id'        => new FieldConfigId(
                    'extend',
                    'Test\SourceEntity',
                    'rel_m_t_o',
                    'manyToOne'
                ),
                'owner'           => true,
                'target_entity'   => 'Test\TargetEntity',
                'target_field_id' => new FieldConfigId(
                    'extend',
                    'Test\TargetEntity',
                    'rev_rel_m_t_o',
                    'oneToMany'
                )
            ],
            'oneToMany|Test\SourceEntity|Test\TargetEntity|rel_o_t_m'  => [
                'field_id'        => new FieldConfigId(
                    'extend',
                    'Test\SourceEntity',
                    'rel_o_t_m',
                    'oneToMany'
                ),
                'owner'           => false,
                'target_entity'   => 'Test\TargetEntity',
                'target_field_id' => new FieldConfigId(
                    'extend',
                    'Test\TargetEntity',
                    'sourceentity_rel_o_t_m',
                    'manyToOne'
                )
            ],
            'manyToMany|Test\SourceEntity|Test\TargetEntity|rel_m_t_m' => [
                'field_id'        => new FieldConfigId(
                    'extend',
                    'Test\SourceEntity',
                    'rel_m_t_m',
                    'manyToMany'
                ),
                'owner'           => true,
                'target_entity'   => 'Test\TargetEntity',
                'target_field_id' => new FieldConfigId(
                    'extend',
                    'Test\TargetEntity',
                    'sourceentity_rel_m_t_m',
                    'manyToMany'
                )
            ]
        ];

        $targetRelations = [
            'manyToOne|Test\SourceEntity|Test\TargetEntity|rel_m_t_o'  => [
                'field_id'        => new FieldConfigId(
                    'extend',
                    'Test\TargetEntity',
                    'rev_rel_m_t_o',
                    'oneToMany'
                ),
                'owner'           => false,
                'target_entity'   => 'Test\SourceEntity',
                'target_field_id' => new FieldConfigId(
                    'extend',
                    'Test\SourceEntity',
                    'rel_m_t_o',
                    'manyToOne'
                )
            ],
            'oneToMany|Test\SourceEntity|Test\TargetEntity|rel_o_t_m'  => [
                'field_id'        => new FieldConfigId(
                    'extend',
                    'Test\TargetEntity',
                    'sourceentity_rel_o_t_m',
                    'manyToOne'
                ),
                'owner'           => true,
                'target_entity'   => 'Test\SourceEntity',
                'target_field_id' => new FieldConfigId(
                    'extend',
                    'Test\SourceEntity',
                    'rel_o_t_m',
                    'oneToMany'
                )
            ],
            'manyToMany|Test\SourceEntity|Test\TargetEntity|rel_m_t_m' => [
                'field_id'        => new FieldConfigId(
                    'extend',
                    'Test\TargetEntity',
                    'sourceentity_rel_m_t_m',
                    'manyToMany'
                ),
                'owner'           => false,
                'target_entity'   => 'Test\SourceEntity',
                'target_field_id' => new FieldConfigId(
                    'extend',
                    'Test\SourceEntity',
                    'rel_m_t_m',
                    'manyToMany'
                )
            ]
        ];

        $extendConfigProvider->addEntityConfig('Test\SourceEntity', ['relation' => $selfRelations]);
        $entityConfigProvider->addEntityConfig('Test\SourceEntity', ['label' => 'Source Entity']);
        $extendConfigProvider->addFieldConfig(
            'Test\SourceEntity',
            'rel_m_t_o',
            'manyToOne',
            ['state' => ExtendScope::STATE_ACTIVE]
        );
        $entityConfigProvider->addFieldConfig(
            'Test\SourceEntity',
            'rel_m_t_o',
            'manyToOne',
            ['label' => 'Rel Many-To-One']
        );
        $extendConfigProvider->addFieldConfig(
            'Test\SourceEntity',
            'rel_o_t_m',
            'oneToMany',
            ['state' => ExtendScope::STATE_ACTIVE]
        );
        $entityConfigProvider->addFieldConfig(
            'Test\SourceEntity',
            'rel_o_t_m',
            'oneToMany',
            ['label' => 'Rel One-To-Many']
        );
        $extendConfigProvider->addFieldConfig(
            'Test\SourceEntity',
            'rel_m_t_m',
            'manyToMany',
            ['state' => ExtendScope::STATE_ACTIVE]
        );
        $entityConfigProvider->addFieldConfig(
            'Test\SourceEntity',
            'rel_m_t_m',
            'manyToMany',
            ['label' => 'Rel Many-To-Many']
        );

        $extendConfigProvider->addEntityConfig('Test\TargetEntity', ['relation' => $targetRelations]);
        $entityConfigProvider->addEntityConfig('Test\TargetEntity', ['label' => 'Target Entity']);
        $extendConfigProvider->addFieldConfig(
            'Test\TargetEntity',
            'rev_rel_m_t_o',
            'oneToMany',
            ['state' => ExtendScope::STATE_ACTIVE]
        );
        $entityConfigProvider->addFieldConfig(
            'Test\TargetEntity',
            'rev_rel_m_t_o',
            'oneToMany',
            ['label' => 'Reverse Rel Many-To-One']
        );
        $extendConfigProvider->addFieldConfig(
            'Test\TargetEntity',
            'sourceentity_rel_o_t_m',
            'oneToMany',
            ['state' => ExtendScope::STATE_ACTIVE]
        );
        $entityConfigProvider->addFieldConfig(
            'Test\TargetEntity',
            'sourceentity_rel_o_t_m',
            'oneToMany',
            ['label' => 'Rel One-To-Many']
        );
        $extendConfigProvider->addFieldConfig(
            'Test\TargetEntity',
            'sourceentity_rel_m_t_m',
            'manyToMany',
            ['state' => ExtendScope::STATE_ACTIVE]
        );
        $entityConfigProvider->addFieldConfig(
            'Test\TargetEntity',
            'sourceentity_rel_m_t_m',
            'manyToMany',
            ['label' => 'Rel Many-To-Many']
        );
    }

    public function testTypeForSourceEntityWithAlreadyCreatedReverseRelationsMarkedAsToBeDeleted()
    {
        $this->prepareRelationsWithReverseRelationsMarkedAsToBeDeleted();

        $form = $this->factory->create($this->type, null, ['class_name' => 'Test\SourceEntity']);

        $this->assertSame(
            $this->defaultFieldTypeChoices,
            $form->offsetGet('type')->getConfig()->getOption('choices')
        );

        $form->submit(['fieldName' => 'field1', 'type' => 'string']);
        $this->assertTrue($form->isSynchronized());
    }

    public function testTypeForTargetEntityWithAlreadyCreatedReverseRelationsMarkedAsToBeDeleted()
    {
        $this->prepareRelationsWithReverseRelationsMarkedAsToBeDeleted();

        $form = $this->factory->create($this->type, null, ['class_name' => 'Test\TargetEntity']);

        $expectedChoices = $this->defaultFieldTypeChoices;

        $expectedChoices[self::RELATIONS_GROUP] = array_merge(
            $expectedChoices[self::RELATIONS_GROUP],
            [
                'manyToMany|Test\SourceEntity|Test\TargetEntity|rel_m_t_m||sourceentity_rel_m_t_m' =>
                    'Reuse "Rel Many-To-Many" of Source Entity',
                'manyToOne|Test\SourceEntity|Test\TargetEntity|rel_m_t_o||rev_rel_m_t_o'           =>
                    'Reuse "Rel Many-To-One" of Source Entity',
                'oneToMany|Test\SourceEntity|Test\TargetEntity|rel_o_t_m||sourceentity_rel_o_t_m'  =>
                    'Reuse "Rel One-To-Many" of Source Entity',
            ]
        );
        $this->assertSame(
            $expectedChoices,
            $form->offsetGet('type')->getConfig()->getOption('choices')
        );

        $form->submit(['fieldName' => 'field1', 'type' => 'string']);
        $this->assertTrue($form->isSynchronized());
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    protected function prepareRelationsWithReverseRelationsMarkedAsToBeDeleted()
    {
        $extendConfigProvider = new ConfigProviderMock($this->configManager, 'extend');
        $entityConfigProvider = new ConfigProviderMock($this->configManager, 'entity');
        $this->configManager->expects($this->any())
            ->method('getProvider')
            ->willReturnMap(
                [
                    ['extend', $extendConfigProvider],
                    ['entity', $entityConfigProvider]
                ]
            );

        $selfRelations = [
            'manyToOne|Test\SourceEntity|Test\TargetEntity|rel_m_t_o'  => [
                'field_id'        => new FieldConfigId(
                    'extend',
                    'Test\SourceEntity',
                    'rel_m_t_o',
                    'manyToOne'
                ),
                'owner'           => true,
                'target_entity'   => 'Test\TargetEntity',
                'target_field_id' => new FieldConfigId(
                    'extend',
                    'Test\TargetEntity',
                    'rev_rel_m_t_o',
                    'oneToMany'
                )
            ],
            'oneToMany|Test\SourceEntity|Test\TargetEntity|rel_o_t_m'  => [
                'field_id'        => new FieldConfigId(
                    'extend',
                    'Test\SourceEntity',
                    'rel_o_t_m',
                    'oneToMany'
                ),
                'owner'           => false,
                'target_entity'   => 'Test\TargetEntity',
                'target_field_id' => new FieldConfigId(
                    'extend',
                    'Test\TargetEntity',
                    'sourceentity_rel_o_t_m',
                    'manyToOne'
                )
            ],
            'manyToMany|Test\SourceEntity|Test\TargetEntity|rel_m_t_m' => [
                'field_id'        => new FieldConfigId(
                    'extend',
                    'Test\SourceEntity',
                    'rel_m_t_m',
                    'manyToMany'
                ),
                'owner'           => true,
                'target_entity'   => 'Test\TargetEntity',
                'target_field_id' => new FieldConfigId(
                    'extend',
                    'Test\TargetEntity',
                    'sourceentity_rel_m_t_m',
                    'manyToMany'
                )
            ]
        ];

        $targetRelations = [
            'manyToOne|Test\SourceEntity|Test\TargetEntity|rel_m_t_o'  => [
                'field_id'        => new FieldConfigId(
                    'extend',
                    'Test\TargetEntity',
                    'rev_rel_m_t_o',
                    'oneToMany'
                ),
                'owner'           => false,
                'target_entity'   => 'Test\SourceEntity',
                'target_field_id' => new FieldConfigId(
                    'extend',
                    'Test\SourceEntity',
                    'rel_m_t_o',
                    'manyToOne'
                )
            ],
            'oneToMany|Test\SourceEntity|Test\TargetEntity|rel_o_t_m'  => [
                'field_id'        => new FieldConfigId(
                    'extend',
                    'Test\TargetEntity',
                    'sourceentity_rel_o_t_m',
                    'manyToOne'
                ),
                'owner'           => true,
                'target_entity'   => 'Test\SourceEntity',
                'target_field_id' => new FieldConfigId(
                    'extend',
                    'Test\SourceEntity',
                    'rel_o_t_m',
                    'oneToMany'
                )
            ],
            'manyToMany|Test\SourceEntity|Test\TargetEntity|rel_m_t_m' => [
                'field_id'        => new FieldConfigId(
                    'extend',
                    'Test\TargetEntity',
                    'sourceentity_rel_m_t_m',
                    'manyToMany'
                ),
                'owner'           => false,
                'target_entity'   => 'Test\SourceEntity',
                'target_field_id' => new FieldConfigId(
                    'extend',
                    'Test\SourceEntity',
                    'rel_m_t_m',
                    'manyToMany'
                )
            ]
        ];

        $extendConfigProvider->addEntityConfig('Test\SourceEntity', ['relation' => $selfRelations]);
        $entityConfigProvider->addEntityConfig('Test\SourceEntity', ['label' => 'Source Entity']);
        $extendConfigProvider->addFieldConfig(
            'Test\SourceEntity',
            'rel_m_t_o',
            'manyToOne',
            ['state' => ExtendScope::STATE_ACTIVE]
        );
        $entityConfigProvider->addFieldConfig(
            'Test\SourceEntity',
            'rel_m_t_o',
            'manyToOne',
            ['label' => 'Rel Many-To-One']
        );
        $extendConfigProvider->addFieldConfig(
            'Test\SourceEntity',
            'rel_o_t_m',
            'oneToMany',
            ['state' => ExtendScope::STATE_ACTIVE]
        );
        $entityConfigProvider->addFieldConfig(
            'Test\SourceEntity',
            'rel_o_t_m',
            'oneToMany',
            ['label' => 'Rel One-To-Many']
        );
        $extendConfigProvider->addFieldConfig(
            'Test\SourceEntity',
            'rel_m_t_m',
            'manyToMany',
            ['state' => ExtendScope::STATE_ACTIVE]
        );
        $entityConfigProvider->addFieldConfig(
            'Test\SourceEntity',
            'rel_m_t_m',
            'manyToMany',
            ['label' => 'Rel Many-To-Many']
        );

        $extendConfigProvider->addEntityConfig('Test\TargetEntity', ['relation' => $targetRelations]);
        $entityConfigProvider->addEntityConfig('Test\TargetEntity', ['label' => 'Target Entity']);
        $extendConfigProvider->addFieldConfig(
            'Test\TargetEntity',
            'rev_rel_m_t_o',
            'oneToMany',
            ['state' => ExtendScope::STATE_DELETE]
        );
        $entityConfigProvider->addFieldConfig(
            'Test\TargetEntity',
            'rev_rel_m_t_o',
            'oneToMany',
            ['label' => 'Reverse Rel Many-To-One']
        );
        $extendConfigProvider->addFieldConfig(
            'Test\TargetEntity',
            'sourceentity_rel_o_t_m',
            'oneToMany',
            ['state' => ExtendScope::STATE_DELETE]
        );
        $entityConfigProvider->addFieldConfig(
            'Test\TargetEntity',
            'sourceentity_rel_o_t_m',
            'oneToMany',
            ['label' => 'Rel One-To-Many']
        );
        $extendConfigProvider->addFieldConfig(
            'Test\TargetEntity',
            'sourceentity_rel_m_t_m',
            'manyToMany',
            ['state' => ExtendScope::STATE_DELETE]
        );
        $entityConfigProvider->addFieldConfig(
            'Test\TargetEntity',
            'sourceentity_rel_m_t_m',
            'manyToMany',
            ['label' => 'Rel Many-To-Many']
        );
    }
}
