<?php

namespace Oro\Bundle\FormBundle\Tests\Unit\Form\Type;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\FormBundle\Form\Type\OroEntityCreateOrSelectType;
use Oro\Bundle\FormBundle\Tests\Unit\Form\Stub\TestEntity;
use Oro\Bundle\FormBundle\Tests\Unit\Form\Stub\TestEntityType;
use Oro\Component\Testing\Unit\PreloadedExtension;
use Symfony\Component\Form\Exception\InvalidConfigurationException;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Test\FormIntegrationTestCase;
use Symfony\Component\PropertyAccess\PropertyPath;

class OroEntityCreateOrSelectTypeTest extends FormIntegrationTestCase
{
    /** @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject */
    private $managerRegistry;

    /** @var OroEntityCreateOrSelectType */
    private $formType;

    protected function setUp(): void
    {
        $metadata = $this->createMock(ClassMetadata::class);
        $metadata->expects($this->any())
            ->method('getSingleIdentifierFieldName')
            ->willReturn('id');
        $metadata->expects($this->any())
            ->method('getName')
            ->willReturn(TestEntity::class);

        $repository = $this->createMock(EntityRepository::class);
        $repository->expects($this->any())
            ->method('find')
            ->willReturnCallback(function ($id) {
                return new TestEntity($id);
            });

        $entityManager = $this->createMock(EntityManager::class);
        $entityManager->expects($this->any())
            ->method('getClassMetadata')
            ->with(TestEntity::class)
            ->willReturn($metadata);
        $entityManager->expects($this->any())
            ->method('getRepository')
            ->with(TestEntity::class)
            ->willReturn($repository);

        $this->managerRegistry = $this->createMock(ManagerRegistry::class);
        $this->managerRegistry->expects($this->any())
            ->method('getManagerForClass')
            ->with(TestEntity::class)
            ->willReturn($entityManager);
        $this->managerRegistry->expects($this->any())
            ->method('getRepository')
            ->with(TestEntity::class)
            ->willReturn($repository);

        $doctrineHelper = $this->createMock(DoctrineHelper::class);
        $doctrineHelper->expects($this->any())
            ->method('getSingleEntityIdentifier')
            ->with($this->isInstanceOf(TestEntity::class))
            ->willReturnCallback(function (TestEntity $entity) {
                return $entity->getId();
            });

        $this->formType = new OroEntityCreateOrSelectType($doctrineHelper);
        parent::setUp();
    }

    /**
     * {@inheritDoc}
     */
    protected function getExtensions(): array
    {
        return [
            new PreloadedExtension([$this->formType], []),
            new EntityCreateSelectFormExtension($this->managerRegistry)
        ];
    }

    /**
     * @dataProvider executeDataProvider
     */
    public function testExecute(
        ?object $inputEntity,
        ?array $submitData,
        ?object $expectedEntity,
        array $inputOptions,
        array $expectedOptions,
        array $expectedViewVars = []
    ) {
        $form = $this->factory->create(OroEntityCreateOrSelectType::class, $inputEntity, $inputOptions);
        foreach ($expectedOptions as $name => $expectedValue) {
            $this->assertTrue($form->getConfig()->hasOption($name));
            $this->assertEquals($expectedValue, $form->getConfig()->getOption($name));
        }

        $form->submit($submitData);
        $this->assertEquals($expectedEntity, $form->getData());

        $formView = $form->createView();
        foreach ($expectedViewVars as $name => $expectedValue) {
            $this->assertArrayHasKey($name, $formView->vars);
            $this->assertEquals($expectedValue, $formView->vars[$name]);
        }
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function executeDataProvider(): array
    {
        return [
            'default without entity' => [
                'inputEntity' => null,
                'submitData' => null,
                'expectedEntity' => null,
                'inputOptions' => [
                    'class' => TestEntity::class,
                    'create_entity_form_type' => TextType::class,
                    'grid_name' => 'test-grid-name',
                    'view_widgets' => [
                        [
                            'route_name' => 'test_route',
                        ]
                    ],
                ],
                'expectedOptions' => [
                    'class' => TestEntity::class,
                    'mode' => OroEntityCreateOrSelectType::MODE_CREATE,
                    'create_entity_form_type' => TextType::class,
                    'create_entity_form_options' => [],
                    'grid_name' => 'test-grid-name',
                    'existing_entity_grid_id' => 'id',
                    'view_widgets' => [
                        [
                            'route_name' => 'test_route',
                            'route_parameters' => ['id' => new PropertyPath('id')],
                            'grid_row_to_route' => ['id' => 'id'],
                        ]
                    ],
                ],
                'expectedViewVars' => [
                    'grid_name' => 'test-grid-name',
                    'existing_entity_grid_id' => 'id',
                    'view_widgets' => [
                        [
                            'route_name' => 'test_route',
                            'route_parameters' => ['id' => null],
                            'grid_row_to_route' => ['id' => 'id'],
                            'widget_alias' => 'oro_entity_create_or_select_test_route',
                        ]
                    ],
                    'value' => [
                        'new_entity' => null,
                        'existing_entity' => null,
                        'mode' => OroEntityCreateOrSelectType::MODE_CREATE
                    ],
                ]
            ],
            'default with entity' => [
                'inputEntity' => new TestEntity(1),
                'submitData' => null,
                'expectedEntity' => null,
                'inputOptions' => [
                    'class' => TestEntity::class,
                    'create_entity_form_type' => TextType::class,
                    'grid_name' => 'test-grid-name',
                    'view_widgets' => [
                        [
                            'route_name' => 'test_route',
                        ]
                    ],
                ],
                'expectedOptions' => [
                    'data_class' => null,
                    'class' => TestEntity::class,
                    'mode' => OroEntityCreateOrSelectType::MODE_CREATE,
                    'create_entity_form_type' => TextType::class,
                    'create_entity_form_options' => [],
                    'grid_name' => 'test-grid-name',
                    'existing_entity_grid_id' => 'id',
                    'view_widgets' => [
                        [
                            'route_name' => 'test_route',
                            'route_parameters' => ['id' => new PropertyPath('id')],
                            'grid_row_to_route' => ['id' => 'id'],
                        ]
                    ],
                ],
                'expectedViewVars' => [
                    'grid_name' => 'test-grid-name',
                    'existing_entity_grid_id' => 'id',
                    'view_widgets' => [
                        [
                            'route_name' => 'test_route',
                            'route_parameters' => ['id' => null],
                            'grid_row_to_route' => ['id' => 'id'],
                            'widget_alias' => 'oro_entity_create_or_select_test_route',
                        ]
                    ],
                    'value' => [
                        'new_entity' => null,
                        'existing_entity' => null,
                        'mode' => OroEntityCreateOrSelectType::MODE_CREATE
                    ],
                ]
            ],
            'create mode' => [
                'inputEntity' => null,
                'submitData' => [
                    'mode' => OroEntityCreateOrSelectType::MODE_CREATE,
                    'new_entity' => ['id' => null],
                ],
                'expectedEntity' => new TestEntity(),
                'inputOptions' => [
                    'class' => TestEntity::class,
                    'create_entity_form_type' => TestEntityType::class,
                    'create_entity_form_options' => [
                        'test_option' => 'default_value'
                    ],
                    'grid_name' => 'test-grid-name',
                    'view_widgets' => [
                        [
                            'route_name' => 'test_route',
                        ]
                    ],
                ],
                'expectedOptions' => [
                    'class' => TestEntity::class,
                    'mode' => OroEntityCreateOrSelectType::MODE_CREATE,
                    'create_entity_form_type' => TestEntityType::class,
                    'create_entity_form_options' => [
                        'test_option' => 'default_value'
                    ],
                    'grid_name' => 'test-grid-name',
                    'existing_entity_grid_id' => 'id',
                    'view_widgets' => [
                        [
                            'route_name' => 'test_route',
                            'route_parameters' => ['id' => new PropertyPath('id')],
                            'grid_row_to_route' => ['id' => 'id'],
                        ]
                    ],
                ],
                'expectedViewVars' => [
                    'grid_name' => 'test-grid-name',
                    'existing_entity_grid_id' => 'id',
                    'view_widgets' => [
                        [
                            'route_name' => 'test_route',
                            'route_parameters' => ['id' => null],
                            'grid_row_to_route' => ['id' => 'id'],
                            'widget_alias' => 'oro_entity_create_or_select_test_route',
                        ]
                    ],
                    'value' => [
                        'new_entity' => new TestEntity(),
                        'existing_entity' => null,
                        'mode' => OroEntityCreateOrSelectType::MODE_CREATE
                    ],
                ]
            ],
            'grid mode' => [
                'inputEntity' => null,
                'submitData' => [
                    'mode' => OroEntityCreateOrSelectType::MODE_GRID,
                ],
                'expectedEntity' => null,
                'inputOptions' => [
                    'class' => TestEntity::class,
                    'mode' => OroEntityCreateOrSelectType::MODE_GRID,
                    'create_entity_form_type' => TestEntityType::class,
                    'grid_name' => 'test-grid-name',
                    'existing_entity_grid_id' => 'key',
                    'view_widgets' => [
                        [
                            'route_name' => 'test_route',
                        ]
                    ],
                ],
                'expectedOptions' => [
                    'class' => TestEntity::class,
                    'mode' => OroEntityCreateOrSelectType::MODE_GRID,
                    'create_entity_form_type' => TestEntityType::class,
                    'create_entity_form_options' => [],
                    'grid_name' => 'test-grid-name',
                    'existing_entity_grid_id' => 'key',
                    'view_widgets' => [
                        [
                            'route_name' => 'test_route',
                            'route_parameters' => ['id' => new PropertyPath('id')],
                            'grid_row_to_route' => ['id' => 'id'],
                        ]
                    ],
                ],
                'expectedViewVars' => [
                    'grid_name' => 'test-grid-name',
                    'existing_entity_grid_id' => 'key',
                    'view_widgets' => [
                        [
                            'route_name' => 'test_route',
                            'route_parameters' => ['id' => null],
                            'grid_row_to_route' => ['id' => 'id'],
                            'widget_alias' => 'oro_entity_create_or_select_test_route',
                        ]
                    ],
                    'value' => [
                        'new_entity' => null,
                        'existing_entity' => null,
                        'mode' => OroEntityCreateOrSelectType::MODE_GRID
                    ],
                ]
            ],
            'view mode' => [
                'inputEntity' => null,
                'submitData' => [
                    'mode' => OroEntityCreateOrSelectType::MODE_VIEW,
                    'existing_entity' => 1
                ],
                'expectedEntity' => new TestEntity(1),
                'inputOptions' => [
                    'class' => TestEntity::class,
                    'create_entity_form_type' => TestEntityType::class,
                    'grid_name' => 'test-grid-name',
                    'view_widgets' => [
                        [
                            'route_name' => 'test_route',
                            'route_parameters' => ['key' => new PropertyPath('id'), 'static' => 'data'],
                            'grid_row_to_route' => ['key' => 'value'],
                        ]
                    ],
                ],
                'expectedOptions' => [
                    'class' => TestEntity::class,
                    'mode' => OroEntityCreateOrSelectType::MODE_CREATE,
                    'create_entity_form_type' => TestEntityType::class,
                    'create_entity_form_options' => [],
                    'grid_name' => 'test-grid-name',
                    'existing_entity_grid_id' => 'id',
                    'view_widgets' => [
                        [
                            'route_name' => 'test_route',
                            'route_parameters' => ['key' => new PropertyPath('id'), 'static' => 'data'],
                            'grid_row_to_route' => ['key' => 'value'],
                        ]
                    ],
                ],
                'expectedViewVars' => [
                    'grid_name' => 'test-grid-name',
                    'existing_entity_grid_id' => 'id',
                    'view_widgets' => [
                        [
                            'route_name' => 'test_route',
                            'route_parameters' => ['key' => 1, 'static' => 'data'],
                            'grid_row_to_route' => ['key' => 'value'],
                            'widget_alias' => 'oro_entity_create_or_select_test_route',
                        ]
                    ],
                    'value' => [
                        'new_entity' => null,
                        'existing_entity' => new TestEntity(1),
                        'mode' => OroEntityCreateOrSelectType::MODE_VIEW
                    ],
                ]
            ],
        ];
    }

    /**
     * @dataProvider executeExceptionDataProvider
     */
    public function testExecuteException(array $options, string $exception, string $message)
    {
        $this->expectException($exception);
        $this->expectExceptionMessage($message);

        $this->factory->create(OroEntityCreateOrSelectType::class, null, $options);
    }

    public function executeExceptionDataProvider(): array
    {
        return [
            'no widget route' => [
                'options' => [
                    'class' => TestEntity::class,
                    'create_entity_form_type' => 'text',
                    'grid_name' => 'test-grid-name',
                    'view_widgets' => [
                        []
                    ],
                ],
                'exception' => InvalidConfigurationException::class,
                'message' => 'Widget route name is not defined',
            ]
        ];
    }
}
