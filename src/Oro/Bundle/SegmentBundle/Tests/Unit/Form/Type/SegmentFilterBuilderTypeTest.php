<?php

namespace Oro\Bundle\SegmentBundle\Tests\Unit\Form\Type;

use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\OrganizationBundle\Entity\BusinessUnit;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\QueryDesignerBundle\QueryDesigner\QueryDefinitionUtil;
use Oro\Bundle\QueryDesignerBundle\Validator\Constraints\NotEmptyFilters;
use Oro\Bundle\SegmentBundle\Entity\Segment;
use Oro\Bundle\SegmentBundle\Entity\SegmentType;
use Oro\Bundle\SegmentBundle\Form\Type\SegmentFilterBuilderType;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Component\Testing\Unit\EntityTrait;
use Oro\Component\Testing\Unit\FormIntegrationTestCase;
use Oro\Component\Testing\Unit\PreloadedExtension;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Validator\Constraints\Valid;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class SegmentFilterBuilderTypeTest extends FormIntegrationTestCase
{
    use EntityTrait;

    /** @var DoctrineHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $doctrineHelper;

    /** @var TokenStorageInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $tokenStorage;

    /** @var SegmentFilterBuilderType */
    private $formType;

    protected function setUp(): void
    {
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);
        $this->tokenStorage = $this->createMock(TokenStorageInterface::class);

        $this->formType = new SegmentFilterBuilderType(
            $this->doctrineHelper,
            $this->tokenStorage
        );
        parent::setUp();
    }

    /**
     * {@inheritDoc}
     */
    protected function getExtensions(): array
    {
        return [
            new PreloadedExtension([$this->formType], []),
            $this->getValidatorExtension(false),
        ];
    }

    public function testConfigureOptionsNonManageableEntityClass()
    {
        $this->expectException(InvalidOptionsException::class);
        $this->expectExceptionMessage('Option segment_entity must be a valid entity class, "stdClass" given');

        $entityClass = \stdClass::class;

        $this->doctrineHelper->expects($this->once())
            ->method('getEntityManagerForClass')
            ->with($entityClass, false)
            ->willReturn(null);

        $options = [
            'segment_entity' => $entityClass
        ];

        $this->factory->create(SegmentFilterBuilderType::class, null, $options);
    }

    /**
     * @dataProvider invalidOptionsDataProvider
     */
    public function testConfigureOptionsUnsupportedOptions(array $options)
    {
        $this->expectException(InvalidOptionsException::class);

        $entityClass = \stdClass::class;

        $em = $this->createMock(EntityManagerInterface::class);
        $this->doctrineHelper->expects($this->any())
            ->method('getEntityManagerForClass')
            ->with($entityClass, false)
            ->willReturn($em);

        $this->factory->create(SegmentFilterBuilderType::class, null, $options);
    }

    public function invalidOptionsDataProvider(): array
    {
        return [
            'segment_entity unsupported type' => [['segment_entity' => []]],
            'segment_columns unsupported type' => [['segment_entity' => \stdClass::class, 'segment_columns' => 'id']],
            'segment_type unsupported type' => [['segment_entity' => \stdClass::class, 'segment_type' => []]],
            'segment_name_template unsupported type' => [
                [
                    'segment_entity' => \stdClass::class,
                    'segment_name_template' => []
                ]
            ],
            'segment_type unknown value' => [['segment_entity' => \stdClass::class, 'segment_type' => 'some_type']],
            'add_name_field unsupported type' => [
                [
                    'segment_entity' => \stdClass::class,
                    'add_name_field' => 123
                ]
            ],
            'name_field_required unsupported type' => [
                [
                    'segment_entity' => \stdClass::class,
                    'name_field_required' => 123
                ]
            ],
        ];
    }

    /**
     * @dataProvider defaultsAndAutoFillOptionsDataProvider
     */
    public function testConfigureOptionsDefaultsAndAutoFill(array $options, array $expected)
    {
        $this->assertNormalizersCalls(\stdClass::class);

        $form = $this->factory->create(SegmentFilterBuilderType::class, null, $options);

        $actualOptions = $form->getConfig()->getOptions();

        foreach ($expected as $key => $expectedValue) {
            $this->assertEquals($expectedValue, $actualOptions[$key]);
        }

        $this->assertArrayHasKey('constraints', $actualOptions);
        $this->assertNotEmpty($actualOptions['constraints']);
        $this->assertGreaterThan(
            0,
            array_reduce($actualOptions['constraints'], function ($carry, $item) {
                return is_a($item, NotEmptyFilters::class) ? $carry + 1 : 0;
            })
        );
    }

    public function defaultsAndAutoFillOptionsDataProvider(): array
    {
        return [
            'defaults' => [
                'options' => [
                    'segment_entity' => \stdClass::class
                ],
                'expected' => [
                    'segment_entity' => \stdClass::class,
                    'data_class' => Segment::class,
                    'segment_type' => SegmentType::TYPE_DYNAMIC,
                    'segment_columns' => ['id'],
                    'segment_name_template' => 'Auto generated segment %s',
                    'add_name_field' => false,
                    'name_field_required' => false,
                    'attr' => ['data-role' => 'query-designer-container']
                ]
            ],
            'name_field_required' => [
                'options' => [
                    'segment_entity' => \stdClass::class,
                    'add_name_field' => true,
                    'name_field_required' => true,
                ],
                'expected' => [
                    'segment_entity' => \stdClass::class,
                    'data_class' => Segment::class,
                    'segment_type' => SegmentType::TYPE_DYNAMIC,
                    'segment_columns' => ['id'],
                    'segment_name_template' => 'Auto generated segment %s',
                    'add_name_field' => true,
                    'name_field_required' => true,
                    'attr' => ['data-role' => 'query-designer-container']
                ]
            ],
            'add NotEmptyFilters constraint if required option is true' => [
                'options' => [
                    'required' => true,
                    'segment_entity' => \stdClass::class,
                    'constraints' => new Valid()
                ],
                'expected' => [
                    'required' => true,
                    'segment_entity' => \stdClass::class,
                    'constraints' => [new Valid(), new NotEmptyFilters()]
                ]
            ]
        ];
    }

    /**
     * @dataProvider formDataProvider
     */
    public function testSubmitNew(array $data, array $expectedDefinition, string $segmentName)
    {
        $entityClass = \stdClass::class;
        $options = [
            'segment_entity' => $entityClass,
            'add_name_field' => true
        ];
        $this->assertNormalizersCalls($entityClass);
        $segmentType = new SegmentType(SegmentType::TYPE_DYNAMIC);
        $this->doctrineHelper->expects($this->once())
            ->method('getEntityReference')
            ->with(SegmentType::class, SegmentType::TYPE_DYNAMIC)
            ->willReturn($segmentType);

        $owner = new BusinessUnit();
        $organization = new Organization();
        $user = $this->createMock(User::class);
        $user->expects($this->once())
            ->method('getOwner')
            ->willReturn($owner);
        $user->expects($this->once())
            ->method('getOrganization')
            ->willReturn($organization);
        $token = $this->createMock(TokenInterface::class);
        $token->expects($this->once())
            ->method('getUser')
            ->willReturn($user);
        $this->tokenStorage->expects($this->once())
            ->method('getToken')
            ->willReturn($token);

        $form = $this->factory->create(SegmentFilterBuilderType::class, null, $options);

        $form->submit($data);
        /** @var Segment $submittedData */
        $submittedData = $form->getData();
        $this->assertInstanceOf(Segment::class, $submittedData);
        $this->assertEquals($segmentType, $submittedData->getType());
        $this->assertEquals($owner, $submittedData->getOwner());
        $this->assertEquals($organization, $submittedData->getOrganization());
        self::assertStringContainsString($segmentName, $submittedData->getName());
        $this->assertJsonStringEqualsJsonString(
            QueryDefinitionUtil::encodeDefinition($expectedDefinition),
            $submittedData->getDefinition()
        );
    }

    /**
     * @dataProvider formDataProvider
     */
    public function testSubmitNewWhenNoUserInStorage(array $data, array $expectedDefinition, string $segmentName)
    {
        $entityClass = \stdClass::class;
        $options = [
            'segment_entity' => $entityClass,
            'add_name_field' => true
        ];
        $this->assertNormalizersCalls($entityClass);
        $segmentType = new SegmentType(SegmentType::TYPE_DYNAMIC);
        $this->doctrineHelper->expects($this->once())
            ->method('getEntityReference')
            ->with(SegmentType::class, SegmentType::TYPE_DYNAMIC)
            ->willReturn($segmentType);

        $user = new \stdClass();
        $token = $this->createMock(TokenInterface::class);
        $token->expects($this->once())
            ->method('getUser')
            ->willReturn($user);
        $this->tokenStorage->expects($this->once())
            ->method('getToken')
            ->willReturn($token);

        $form = $this->factory->create(SegmentFilterBuilderType::class, null, $options);

        $form->submit($data);
        /** @var Segment $submittedData */
        $submittedData = $form->getData();
        $this->assertInstanceOf(Segment::class, $submittedData);
        $this->assertEquals($segmentType, $submittedData->getType());
        $this->assertNull($submittedData->getOwner());
        $this->assertNull($submittedData->getOrganization());
        self::assertStringContainsString($segmentName, $submittedData->getName());
        $this->assertJsonStringEqualsJsonString(
            QueryDefinitionUtil::encodeDefinition($expectedDefinition),
            $submittedData->getDefinition()
        );
    }

    /**
     * @dataProvider formDataProvider
     */
    public function testSubmitExisting(array $data, array $expectedDefinition, string $segmentName)
    {
        $entityClass = \stdClass::class;
        $options = [
            'segment_entity' => $entityClass,
            'segment_columns' => ['id'],
            'add_name_field' => true
        ];

        $em = $this->createMock(EntityManagerInterface::class);
        $this->doctrineHelper->expects($this->once())
            ->method('getEntityManagerForClass')
            ->with($entityClass, false)
            ->willReturn($em);

        $this->doctrineHelper->expects($this->never())
            ->method('getEntityReference');
        $this->tokenStorage->expects($this->never())
            ->method('getToken');
        $existingEntity = $this->getEntity(Segment::class, ['id' => 2]);

        $form = $this->factory->create(SegmentFilterBuilderType::class, $existingEntity, $options);

        $form->submit($data);
        /** @var Segment $submittedData */
        $submittedData = $form->getData();
        self::assertStringContainsString($segmentName, $submittedData->getName());
        $this->assertInstanceOf(Segment::class, $submittedData);
        $this->assertJsonStringEqualsJsonString(
            QueryDefinitionUtil::encodeDefinition($expectedDefinition),
            $submittedData->getDefinition()
        );
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function formDataProvider(): array
    {
        return [
            'without columns' => [
                'data' => [
                    'entity' => \stdClass::class,
                    'definition' => QueryDefinitionUtil::encodeDefinition([
                        'filters' => [
                            [
                                'columnName' => 'id',
                                'criterion' => [
                                    'filter' => 'number',
                                    'data' => ['value' => 10, 'type' => 3]
                                ]
                            ]
                        ]
                    ])
                ],
                'expected definition' => [
                    'filters' => [
                        [
                            'columnName' => 'id',
                            'criterion' => [
                                'filter' => 'number',
                                'data' => ['value' => 10, 'type' => 3]
                            ]
                        ]
                    ],
                    'columns' => [
                        [
                            'name' => 'id',
                            'label' => 'id',
                            'sorting' => null,
                            'func' => null
                        ]
                    ]
                ],
                'generated segment name' => 'Auto generated segment'
            ],
            'with columns' => [
                'data' => [
                    'entity' => \stdClass::class,
                    'definition' => QueryDefinitionUtil::encodeDefinition([
                        'filters' => [
                            [
                                'columnName' => 'id',
                                'criterion' => [
                                    'filter' => 'number',
                                    'data' => ['value' => 10, 'type' => 3]
                                ]
                            ]
                        ],
                        'columns' => [
                            [
                                'name' => 'id',
                                'label' => 'ID column',
                                'sorting' => null,
                                'func' => null
                            ]
                        ]
                    ])
                ],
                'expected definition' => [
                    'filters' => [
                        [
                            'columnName' => 'id',
                            'criterion' => [
                                'filter' => 'number',
                                'data' => ['value' => 10, 'type' => 3]
                            ]
                        ]
                    ],
                    'columns' => [
                        [
                            'name' => 'id',
                            'label' => 'ID column',
                            'sorting' => null,
                            'func' => null
                        ]
                    ]
                ],
                'generated segment name' => 'Auto generated segment'
            ],
            'with custom name' => [
                'data' => [
                    'entity' => \stdClass::class,
                    'definition' => QueryDefinitionUtil::encodeDefinition([
                        'filters' => [
                            [
                                'columnName' => 'id',
                                'criterion' => [
                                    'filter' => 'number',
                                    'data' => ['value' => 10, 'type' => 3]
                                ]
                            ]
                        ]
                    ]),
                    'name' => 'Segment custom name'
                ],
                'expected definition' => [
                    'filters' => [
                        [
                            'columnName' => 'id',
                            'criterion' => [
                                'filter' => 'number',
                                'data' => ['value' => 10, 'type' => 3]
                            ]
                        ]
                    ],
                    'columns' => [
                        [
                            'name' => 'id',
                            'label' => 'id',
                            'sorting' => null,
                            'func' => null
                        ]
                    ]
                ],
                'generated segment name' => 'Segment custom name'
            ],
        ];
    }

    public function testSubmitExistingWhenNoNameField()
    {
        $entityClass = \stdClass::class;
        $options = [
            'segment_entity' => $entityClass,
            'segment_columns' => ['id'],
            'add_name_field' => false
        ];

        $em = $this->createMock(EntityManagerInterface::class);
        $this->doctrineHelper->expects($this->once())
            ->method('getEntityManagerForClass')
            ->with($entityClass, false)
            ->willReturn($em);

        $existingName = 'Some name';
        /** @var Segment $existingEntity */
        $existingEntity = $this->getEntity(Segment::class, ['id' => 2, 'name' => $existingName]);

        $form = $this->factory->create(SegmentFilterBuilderType::class, $existingEntity, $options);

        $form->submit([]);
        $this->assertEquals($existingName, $existingEntity->getName());
    }

    public function testEventListenersOptions()
    {
        $isCalled = false;
        $entityClass = \stdClass::class;
        $options = [
            'segment_entity' => $entityClass,
            'segment_columns' => ['id'],
            'add_name_field' => true,
            'field_event_listeners' => [
                'definition' => [
                    FormEvents::PRE_SET_DATA => function () use (&$isCalled) {
                        $isCalled = true;
                    }
                ]
            ]
        ];

        $em = $this->createMock(EntityManagerInterface::class);
        $this->doctrineHelper->expects($this->once())
            ->method('getEntityManagerForClass')
            ->with($entityClass, false)
            ->willReturn($em);

        $this->doctrineHelper->expects($this->never())
            ->method('getEntityReference');
        $this->tokenStorage->expects($this->never())
            ->method('getToken');

        $existingEntity = $this->getEntity(Segment::class, ['id' => 2]);

        $this->factory->create(SegmentFilterBuilderType::class, $existingEntity, $options);

        $this->assertTrue($isCalled);
    }

    public function testBuildView()
    {
        $formView = new FormView();

        $form = $this->createMock(FormInterface::class);

        $conditionBuilderValidation = [
            'condition-item' =>  [
                'NotBlank' => ['message' => 'Condition should not be blank'],
            ],
        ];

        $fieldConditionOptions = [
            'fieldChoice' => [
                'exclude' => [
                    ['name' => 'FieldName', 'type' => 'enum', 'entityClassName' => \stdClass::class]
                ]
            ]
        ];

        $options = [
            'condition_builder_validation' => $conditionBuilderValidation,
            'field_condition_options' => $fieldConditionOptions
        ];

        $this->formType->buildView($formView, $form, $options);

        $this->assertArrayHasKey('condition_builder_options', $formView->vars);
        $this->assertArrayHasKey('field_condition_options', $formView->vars);

        $this->assertEquals(
            ['validation' => $conditionBuilderValidation],
            $formView->vars['condition_builder_options']
        );

        $this->assertEquals($fieldConditionOptions, $formView->vars['field_condition_options']);
    }

    private function assertNormalizersCalls(string $entityClass): void
    {
        $em = $this->createMock(EntityManagerInterface::class);
        $this->doctrineHelper->expects($this->once())
            ->method('getEntityManagerForClass')
            ->with($entityClass, false)
            ->willReturn($em);
        $this->doctrineHelper->expects($this->once())
            ->method('getSingleEntityIdentifierFieldName')
            ->with($entityClass)
            ->willReturn('id');
    }
}
