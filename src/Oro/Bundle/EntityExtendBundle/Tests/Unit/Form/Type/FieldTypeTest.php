<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\Form\Type;

use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityConfigBundle\Tests\Unit\ConfigProviderMock;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\EntityExtendBundle\Form\Type\FieldType;
use Oro\Bundle\EntityExtendBundle\Provider\FieldTypeProvider;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendDbIdentifierNameGenerator;
use Oro\Bundle\EntityExtendBundle\Validator\Constraints\FieldNameLength;
use Oro\Bundle\FormBundle\Form\Extension\DataBlockExtension;
use Oro\Bundle\FormBundle\Form\Extension\JsValidation\ConstraintsProviderInterface;
use Oro\Bundle\FormBundle\Form\Extension\JsValidationExtension;
use Oro\Bundle\TranslationBundle\Form\Extension\TranslatableChoiceTypeExtension;
use Oro\Bundle\TranslationBundle\Translation\Translator;
use Oro\Component\Testing\Unit\PreloadedExtension;
use Symfony\Component\Form\ChoiceList\View\ChoiceGroupView;
use Symfony\Component\Form\ChoiceList\View\ChoiceView;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Validator\Type\FormTypeValidatorExtension;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\Form\Test\TypeTestCase;
use Symfony\Component\Translation\IdentityTranslator;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\ConstraintValidatorFactory;
use Symfony\Component\Validator\Context\ExecutionContextFactory;
use Symfony\Component\Validator\Mapping\Factory\LazyLoadingMetadataFactory;
use Symfony\Component\Validator\Mapping\Loader\LoaderChain;
use Symfony\Component\Validator\Validator\RecursiveValidator;

class FieldTypeTest extends TypeTestCase
{
    const FIELDS_GROUP = 'oro.entity_extend.form.data_type_group.fields';
    const RELATIONS_GROUP = 'oro.entity_extend.form.data_type_group.relations';

    /** @var FieldType $type */
    protected $type;

    /** @var \PHPUnit\Framework\MockObject\MockObject|ConfigManager */
    protected $configManager;

    /** @var \PHPUnit\Framework\MockObject\MockObject|Translator */
    protected $translator;

    /** @var \PHPUnit\Framework\MockObject\MockObject|FieldTypeProvider */
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

    /** @var array */
    protected $expectedChoicesView;

    protected function setUp()
    {
        $this->expectedChoicesView = $this->prepareExpectedChoicesView($this->defaultFieldTypeChoices);

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
        parent::setUp();
    }

    /**
     * @param array $defaultFieldTypeChoices
     * @param array $attributes
     *
     * @return array
     */
    protected function prepareExpectedChoicesView($defaultFieldTypeChoices, array $attributes = [])
    {
        $choiceCounter = 0;
        $expectedChoicesView = [];
        foreach ($defaultFieldTypeChoices as $fieldGroup => $fields) {
            $preparedFields = [];
            foreach ($fields as $fieldValue => $fieldLabel) {
                $preparedFields[$choiceCounter++] = new ChoiceView(
                    $fieldValue,
                    $fieldValue,
                    $fieldLabel,
                    !empty($attributes[$fieldValue]) ? $attributes[$fieldValue] : []
                );
            }
            $expectedChoicesView[$fieldGroup] = new ChoiceGroupView(
                $fieldGroup,
                $preparedFields
            );
        }
        return $expectedChoicesView;
    }

    /**
     * @return array
     */
    protected function getExtensions()
    {
        $validator = new RecursiveValidator(
            new ExecutionContextFactory(new IdentityTranslator()),
            new LazyLoadingMetadataFactory(new LoaderChain([])),
            new ConstraintValidatorFactory()
        );

        /** @var ConstraintsProviderInterface $constraintsProvider */
        $constraintsProvider = $this->createMock(ConstraintsProviderInterface::class);
        $constraintsProvider->expects($this->any())
            ->method('getFormConstraints')
            ->willReturnCallback(
                function (FormInterface $form) {
                    return $form->getName() === 'fieldName' ? ['NotBlank' => new NotBlank()] : [];
                }
            );

        return [
            new PreloadedExtension(
                [
                    FieldType::class => $this->type
                ],
                [
                    FormType::class => [
                        new DataBlockExtension(),
                        new FormTypeValidatorExtension($validator),
                        new JsValidationExtension($constraintsProvider)
                    ],
                    ChoiceType::class => [
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

    public function testFinishView()
    {
        $fieldNameView = new FormView();
        $fieldNameView->vars['attr']['data-validation'] = '{}';

        $view = new FormView();
        $view->children['fieldName'] = $fieldNameView;

        /** @var $form FormInterface|\PHPUnit\Framework\MockObject\MockObject */
        $form = $this->createMock(FormInterface::class);

        $this->type->finishView($view, $form, []);

        $this->assertEquals(
            [
                'data-validation' => \json_encode(
                    [
                        FieldNameLength::class => [
                            'min' => FieldNameLength::MIN_LENGTH,
                            'max' => 22, //will be returned by generator
                        ]
                    ]
                )
            ],
            $fieldNameView->vars['attr']
        );
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

        $form = $this->factory->create(FieldType::class, null, ['class_name' => 'Test\SourceEntity']);

        $this->assertEquals(
            $this->expectedChoicesView,
            $form->createView()->offsetGet('type')->vars['choices']
        );

        $form->submit(['fieldName' => 'name', 'type' => 'string']);
        $this->assertTrue($form->isSynchronized());
    }

    public function testTypeForSourceEntity()
    {
        $this->prepareRelations();

        $form = $this->factory->create(FieldType::class, null, ['class_name' => 'Test\SourceEntity']);

        $this->assertEquals(
            $this->expectedChoicesView,
            $form->createView()->offsetGet('type')->vars['choices']
        );

        $form->submit(['fieldName' => 'field1', 'type' => 'string']);
        $this->assertTrue($form->isSynchronized());
    }

    public function testTypeForTargetEntity()
    {
        $this->prepareRelations();

        $form = $this->factory->create(FieldType::class, null, ['class_name' => 'Test\TargetEntity']);

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

        $attributes = [
            'manyToMany|Test\SourceEntity|Test\TargetEntity|rel_m_t_m||sourceentity_rel_m_t_m' => [
                'data-fieldname' => 'sourceentity_rel_m_t_m',
            ],
            'manyToOne|Test\SourceEntity|Test\TargetEntity|rel_m_t_o||' => [
                'data-fieldname' => '',
            ],
            'oneToMany|Test\SourceEntity|Test\TargetEntity|rel_o_t_m||sourceentity_rel_o_t_m'  => [
                'data-fieldname' => 'sourceentity_rel_o_t_m',
            ]
        ];

        $expectedChoicesView = $this->prepareExpectedChoicesView($expectedChoices, $attributes);

        $this->assertEquals(
            $expectedChoicesView,
            $form->createView()->offsetGet('type')->vars['choices']
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

        $form = $this->factory->create(FieldType::class, null, ['class_name' => 'Test\SourceEntity']);

        $this->assertEquals(
            $this->expectedChoicesView,
            $form->createView()->offsetGet('type')->vars['choices']
        );

        $form->submit(['fieldName' => 'field1', 'type' => 'string']);
        $this->assertTrue($form->isSynchronized());
    }

    public function testTypeForTargetEntityWithAlreadyCreatedReverseRelations()
    {
        $this->prepareRelationsWithReverseRelations();

        $form = $this->factory->create(FieldType::class, null, ['class_name' => 'Test\TargetEntity']);

        $this->assertEquals(
            $this->expectedChoicesView,
            $form->createView()->offsetGet('type')->vars['choices']
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

        $form = $this->factory->create(FieldType::class, null, ['class_name' => 'Test\SourceEntity']);

        $this->assertEquals(
            $this->expectedChoicesView,
            $form->createView()->offsetGet('type')->vars['choices']
        );

        $form->submit(['fieldName' => 'field1', 'type' => 'string']);
        $this->assertTrue($form->isSynchronized());
    }

    public function testTypeForTargetEntityWithAlreadyCreatedReverseRelationsMarkedAsToBeDeleted()
    {
        $this->prepareRelationsWithReverseRelationsMarkedAsToBeDeleted();

        $form = $this->factory->create(FieldType::class, null, ['class_name' => 'Test\TargetEntity']);

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

        $attributes = [
            'manyToMany|Test\SourceEntity|Test\TargetEntity|rel_m_t_m||sourceentity_rel_m_t_m' => [
                'data-fieldname' => 'sourceentity_rel_m_t_m',
            ],
            'manyToOne|Test\SourceEntity|Test\TargetEntity|rel_m_t_o||rev_rel_m_t_o' => [
                'data-fieldname' => 'rev_rel_m_t_o',
            ],
            'oneToMany|Test\SourceEntity|Test\TargetEntity|rel_o_t_m||sourceentity_rel_o_t_m' => [
                'data-fieldname' => 'sourceentity_rel_o_t_m',
            ],
        ];

        $expectedChoicesView = $this->prepareExpectedChoicesView($expectedChoices, $attributes);

        $this->assertEquals(
            $expectedChoicesView,
            $form->createView()->offsetGet('type')->vars['choices']
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
