<?php

namespace Oro\Bundle\FormBundle\Tests\Unit\Form\Extension\JsValidation;

use Oro\Bundle\FormBundle\Form\Extension\JsValidation\ConstraintConverterInterface;
use Oro\Bundle\FormBundle\Form\Extension\JsValidation\ConstraintsProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormConfigBuilder;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints\GroupSequence;
use Symfony\Component\Validator\Mapping\ClassMetadata;
use Symfony\Component\Validator\Mapping\Factory\MetadataFactoryInterface;
use Symfony\Component\Validator\Mapping\PropertyMetadata;

class ConstraintsProviderTest extends TestCase
{
    private MetadataFactoryInterface&MockObject $metadataFactory;
    private ConstraintConverterInterface&MockObject $constraintConverter;
    private ConstraintsProvider $constraintsProvider;

    #[\Override]
    protected function setUp(): void
    {
        $this->metadataFactory = $this->createMock(MetadataFactoryInterface::class);
        $this->constraintConverter = $this->createMock(ConstraintConverterInterface::class);

        $this->constraintsProvider = new ConstraintsProvider(
            $this->metadataFactory,
            $this->constraintConverter
        );
    }

    /**
     * @dataProvider getFormConstraintsDataProvider
     */
    public function testGetFormConstraints(
        FormInterface $formView,
        array $expectGetMetadata = [],
        array $expectedConstraints = []
    ): void {
        $this->constraintConverter->expects(self::exactly(count($expectedConstraints)))
            ->method('convertConstraint')
            ->willReturnArgument(0);

        if ($expectGetMetadata) {
            $classMetadata = $this->createMock(ClassMetadata::class);
            $propertyNames = array_keys($expectGetMetadata['propertyConstraints']);

            $classMetadata->expects(self::once())
                ->method('getConstrainedProperties')
                ->willReturn($propertyNames);

            $classMetadata->expects(self::any())
                ->method('getPropertyMetadata')
                ->willReturnCallback(function ($property) use ($expectGetMetadata) {
                    if (isset($expectGetMetadata['propertyConstraints'][$property])) {
                        $propertyMetadata = $this->createMock(PropertyMetadata::class);
                        $propertyMetadata->expects(self::any())
                            ->method('getConstraints')
                            ->willReturn($expectGetMetadata['propertyConstraints'][$property]);
                        return [$propertyMetadata];
                    }
                    return [];
                });

            $this->metadataFactory->expects(self::once())
                ->method('getMetadataFor')
                ->with($expectGetMetadata['value'])
                ->willReturn($classMetadata);
        }

        self::assertEquals(
            $expectedConstraints,
            $this->constraintsProvider->getFormConstraints($formView)
        );
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function getFormConstraintsDataProvider(): array
    {
        return [
            'not_mapped' => [
                'form' => $this->createForm(
                    'email',
                    null,
                    [
                        'mapped' => false,
                        'constraints' => [$this->createConstraint('NotBlank', ['Default'])],
                    ],
                    $this->createForm('user', 'stdClass', [])
                ),
                'expectGetMetadataFor' => [],
                'expectedConstraints' => [$this->createConstraint('NotBlank', ['Default'])],
            ],
            'doesnt_have_parent' => [
                'formView' => $this->createForm(
                    'email',
                    null,
                    [
                        'mapped' => false,
                        'constraints' => [$this->createConstraint('NotBlank', ['Default'])],
                    ]
                ),
                'expectGetMetadataFor' => [],
                'expectedConstraints' => [$this->createConstraint('NotBlank', ['Default'])],
            ],
            'ignore_all_by_groups' => [
                'formView' => $this->createForm(
                    'email',
                    null,
                    [
                        'constraints' => [$this->createConstraint('NotBlank', ['Default'])],
                    ],
                    $this->createForm(
                        'user',
                        'stdClass',
                        [
                            'validation_groups' => ['Custom'],
                        ]
                    )
                ),
                'expectGetMetadataFor' => [
                    'value' => 'stdClass',
                    'propertyConstraints' => [
                        'email' => [$this->createConstraint('Email', ['Default'])],
                    ],
                ],
                'expectedConstraints' => [],
            ],
            'ignore_one_by_groups' => [
                'formView' => $this->createForm(
                    'email',
                    null,
                    [
                        'constraints' => [$this->createConstraint('NotBlank', ['Default'])],
                    ],
                    $this->createForm(
                        'user',
                        'stdClass',
                        [
                            'validation_groups' => ['Custom'],
                        ]
                    )
                ),
                'expectGetMetadataFor' => [
                    'value' => 'stdClass',
                    'propertyConstraints' => [
                        'email' => [$this->createConstraint('Email', ['Custom'])],
                    ],
                ],
                'expectedConstraints' => [$this->createConstraint('Email', ['Custom'])],
            ],
            'filter_by_name' => [
                'formView' => $this->createForm(
                    'email',
                    null,
                    [
                        'name' => 'email',
                        'constraints' => [$this->createConstraint('NotBlank', ['Default'])],
                    ],
                    $this->createForm('user', 'stdClass')
                ),
                'expectGetMetadataFor' => [
                    'value' => 'stdClass',
                    'propertyConstraints' => [
                        'email' => [$this->createConstraint('Email', ['Default'])],
                        'username' => [$this->createConstraint('NotBlank', ['Default'])],
                    ],
                ],
                'expectedConstraints' => [
                    $this->createConstraint('Email', ['Default']),
                    $this->createConstraint('NotBlank', ['Default']),
                ],
            ],
            'entity_class' => [
                'formView' => $this->createForm(
                    'email',
                    null,
                    [
                        'name' => 'email',
                        'constraints' => [$this->createConstraint('NotBlank', ['Default'])],
                    ],
                    $this->createForm('user', null, ['entity_class' => 'stdClass'])
                ),
                'expectGetMetadataFor' => [
                    'value' => 'stdClass',
                    'propertyConstraints' => [
                        'email' => [$this->createConstraint('Email', ['Default'])],
                        'username' => [$this->createConstraint('NotBlank', ['Default'])],
                    ],
                ],
                'expectedConstraints' => [
                    $this->createConstraint('Email', ['Default']),
                    $this->createConstraint('NotBlank', ['Default']),
                ],
            ],
            'with group sequence' => [
                'formView' => $this->createForm(
                    'email',
                    null,
                    [
                        'constraints' => [$this->createConstraint('NotBlank', ['Default'])],
                    ],
                    $this->createForm(
                        'user',
                        'stdClass',
                        [
                            'validation_groups' => new GroupSequence(['Custom']),
                        ]
                    )
                ),
                'expectGetMetadataFor' => [
                    'value' => 'stdClass',
                    'propertyConstraints' => [
                        'email' => [$this->createConstraint('Email', ['Custom'])],
                    ],
                ],
                'expectedConstraints' => [$this->createConstraint('Email', ['Custom'])],
            ],
        ];
    }

    private function createForm(
        string $name,
        ?string $dataClass = null,
        array $options = [],
        ?FormInterface $parent = null
    ): FormInterface {
        $eventDispatcher = $this->createMock(EventDispatcherInterface::class);

        $config = new FormConfigBuilder($name, $dataClass, $eventDispatcher, $options);

        $result = new Form($config);
        $result->setParent($parent);

        return $result;
    }

    private function createConstraint(string $name, array $groups, array $options = []): Constraint
    {
        $className = 'Symfony\\Component\\Validator\\Constraints\\' . $name;

        $result = new $className($options);
        $result->groups = $groups;

        return $result;
    }
}
