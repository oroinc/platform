<?php

namespace Oro\Bundle\FormBundle\Tests\Unit\Form\Type;

use Doctrine\ORM\EntityManager;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\FormBundle\Form\DataTransformer\EntitiesToIdsTransformer;
use Oro\Bundle\FormBundle\Form\DataTransformer\EntityToIdTransformer;
use Oro\Bundle\FormBundle\Form\Exception\FormException;
use Oro\Bundle\FormBundle\Form\Type\EntityIdentifierType;
use Oro\Component\Testing\Unit\PreloadedExtension;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Form\Test\FormBuilderInterface;
use Symfony\Component\Form\Test\FormIntegrationTestCase;

class EntityIdentifierTypeTest extends FormIntegrationTestCase
{
    /**
     * @var EntityIdentifierType */
    private $type;

    /** @var ManagerRegistry|MockObject */
    private $managerRegistry;

    /** @var EntityManager|MockObject */
    private $entityManager;

    /** @var EntitiesToIdsTransformer|MockObject */
    private $entitiesToIdsTransformer;

    protected function setUp(): void
    {
        $this->type = $this->getMockBuilder(EntityIdentifierType::class)
            ->onlyMethods(['createEntitiesToIdsTransformer'])
            ->setConstructorArgs([$this->getMockManagerRegistry()])
            ->getMock();
        $this->type->method('createEntitiesToIdsTransformer')->willReturn($this->getMockEntitiesToIdsTransformer());
        parent::setUp();
    }

    protected function getExtensions(): array
    {
        return [
            new PreloadedExtension([
                EntityIdentifierType::class => $this->type
            ], [])
        ];
    }

    protected function getTestFormType()
    {
        return $this->type;
    }

    /**
     * @dataProvider bindDataProvider
     * @param mixed $bindData
     * @param mixed $formData
     * @param mixed $viewData
     * @param array $options
     * @param array $expectedCalls
     */
    public function testBindData(
        $bindData,
        $formData,
        $viewData,
        array $options,
        array $expectedCalls
    ) {
        if (isset($options['em']) && is_callable($options['em'])) {
            $options['em'] = call_user_func($options['em']);
        }

        foreach ($expectedCalls as $key => $calls) {
            $this->addMockExpectedCalls($key, $calls);
        }

        $form = $this->factory->create(EntityIdentifierType::class, null, $options);

        $form->submit($bindData);

        $this->assertTrue($form->isSynchronized());
        $this->assertEquals($formData, $form->getData());

        $view = $form->createView();
        $this->assertEquals($viewData, $view->vars['value']);
    }

    /**
     * Data provider for testBindData
     *
     * @return array
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function bindDataProvider()
    {
        $entitiesId1234 = $this->createMockEntityList('id', [1, 2, 3, 4]);

        return [
            'default' => [
                '1,2,3,4',
                $entitiesId1234,
                '1,2,3,4',
                ['class' => 'TestClass'],
                'expectedCalls' => [
                    'managerRegistry' => [
                        ['getManagerForClass', ['TestClass'], ['self', 'getMockEntityManager']],
                    ],
                    'entitiesToIdsTransformer' => [
                        ['transform', [null], []],
                        ['reverseTransform', [[1, 2, 3, 4]], $entitiesId1234],
                        ['transform', [$entitiesId1234], [1, 2, 3, 4]],
                    ]
                ]
            ],
            'accept array' => [
                [1, 2, 3, 4],
                $entitiesId1234,
                '1,2,3,4',
                ['class' => 'TestClass'],
                'expectedCalls' => [
                    'managerRegistry' => [
                        ['getManagerForClass', ['TestClass'], ['self', 'getMockEntityManager']],
                    ],
                    'entitiesToIdsTransformer' => [
                        ['transform', [null], []],
                        ['reverseTransform', [[1, 2, 3, 4]], $entitiesId1234],
                        ['transform', [$entitiesId1234], [1, 2, 3, 4]],
                    ]
                ]
            ],
            'custom entity manager name' => [
                '1,2,3,4',
                $entitiesId1234,
                '1,2,3,4',
                ['class' => 'TestClass', 'em' => 'custom_entity_manager'],
                'expectedCalls' => [
                    'managerRegistry' => [
                        ['getManager', ['custom_entity_manager'], ['self', 'getMockEntityManager']],
                    ],
                    'entitiesToIdsTransformer' => [
                        ['transform', [null], []],
                        ['reverseTransform', [[1, 2, 3, 4]], $entitiesId1234],
                        ['transform', [$entitiesId1234], [1, 2, 3, 4]],
                    ]
                ]
            ],
            'custom entity manager object' => [
                '1,2,3,4',
                $entitiesId1234,
                '1,2,3,4',
                ['class' => 'TestClass', 'em' => ['self', 'getMockEntityManager']],
                'expectedCalls' => [
                    'managerRegistry' => [],
                    'entitiesToIdsTransformer' => [
                        ['transform', [null], []],
                        ['reverseTransform', [[1, 2, 3, 4]], $entitiesId1234],
                        ['transform', [$entitiesId1234], [1, 2, 3, 4]],
                    ]
                ]
            ],
            'custom query builder callback' => [
                '1,2,3,4',
                $entitiesId1234,
                '1,2,3,4',
                [
                    'class' => 'TestClass',
                    'queryBuilder' => function ($repository, array $ids) {
                        $result = $repository->createQueryBuilder('o');
                        $result->where('o.id IN (:values)')->setParameter('values', $ids);

                        return $result;
                    }
                ],
                'expectedCalls' => [
                    'managerRegistry' => [
                        ['getManagerForClass', ['TestClass'], ['self', 'getMockEntityManager']],
                    ],
                    'entitiesToIdsTransformer' => [
                        ['transform', [null], []],
                        ['reverseTransform', [[1, 2, 3, 4]], $entitiesId1234],
                        ['transform', [$entitiesId1234], [1, 2, 3, 4]],
                    ]
                ]
            ],
        ];
    }

    /**
     * @dataProvider createErrorsDataProvider
     * @param array  $options
     * @param array  $expectedCalls
     * @param string $expectedException
     * @param string $expectedExceptionMessage
     */
    public function testCreateErrors(
        array $options,
        array $expectedCalls,
        $expectedException,
        $expectedExceptionMessage
    ) {
        foreach ($expectedCalls as $key => $calls) {
            $this->addMockExpectedCalls($key, $calls);
        }

        $this->expectException($expectedException);
        $this->expectExceptionMessage($expectedExceptionMessage);
        $this->factory->create(EntityIdentifierType::class, null, $options);
    }

    /**
     * Data provider for testBindData
     *
     * @return array
     */
    public function createErrorsDataProvider()
    {
        return [
            'cannot resolve entity manager by class' => [
                ['class' => 'TestClass'],
                'expectedCalls' => [
                    'managerRegistry' => [
                        ['getManagerForClass', ['TestClass'], null],
                    ]
                ],
                'expectedException' => FormException::class,
                'expectedExceptionMessage'
                    => 'Class "TestClass" is not a managed Doctrine entity. Did you forget to map it?'
            ],
            'cannot resolve entity manager by name' => [
                ['class' => 'TestClass', 'em' => 'custom_entity_manager'],
                'expectedCalls' => [
                    'managerRegistry' => [
                        ['getManager', ['custom_entity_manager'], null],
                    ]
                ],
                'expectedException' => FormException::class,
                'expectedExceptionMessage'
                    => 'Class "TestClass" is not a managed Doctrine entity. Did you forget to map it?'
            ],
            'invalid em' => [
                ['class' => 'TestClass', 'em' => new \stdClass()],
                'expectedCalls' => [
                    'managerRegistry' => []
                ],
                'expectedException' => FormException::class,
                'expectedExceptionMessage'
                    => 'Option "em" should be a string or entity manager object, stdClass given'
            ],
            'invalid queryBuilder' => [
                ['class' => 'TestClass', 'queryBuilder' => 'invalid'],
                'expectedCalls' => [
                    'managerRegistry' => [
                        ['getManagerForClass', ['TestClass'], ['self', 'getMockEntityManager']],
                    ],
                ],
                'expectedException' => FormException::class,
                'expectedExceptionMessage'
                    => 'Option "queryBuilder" should be a callable, string given'
            ],
        ];
    }

    /**
     * @dataProvider multipleTypeDataProvider
     * @param bool $isMultiple
     */
    public function testCreateEntitiesToIdsTransformer($isMultiple)
    {
        $options = [
            'em' => $this->getMockEntityManager(),
            'multiple' => $isMultiple,
            'class' => 'TestClass',
            'property' => 'id',
            'queryBuilder' => function ($repository, array $ids) {
                return $repository->createQueryBuilder('o')->where('o.id IN (:values)')->setParameter('values', $ids);
            },
            'values_delimiter' => ','
        ];
        /** @var FormBuilderInterface|MockObject $builder */
        $builder = $this->getMockBuilder(FormBuilderInterface::class)
            ->onlyMethods(['addViewTransformer', 'addEventSubscriber'])
            ->getMockForAbstractClass();

        $viewTransformer = $this->createMock(
            $isMultiple ? EntitiesToIdsTransformer::class : EntityToIdTransformer::class
        );

        $builder->expects(static::at(0))
            ->method('addViewTransformer')
            ->with($viewTransformer)
            ->willReturnSelf();

        if ($isMultiple) {
            $builder->expects(static::at(1))
                ->method('addViewTransformer')
                ->willReturnSelf();
        }

        $this->type = $this->getMockBuilder(EntityIdentifierType::class)
            ->setConstructorArgs([$this->getMockManagerRegistry()])
            ->onlyMethods(['createEntitiesToIdsTransformer'])
            ->getMock();

        $this->type->expects(static::once())
            ->method('createEntitiesToIdsTransformer')
            ->with($options)
            ->willReturn($viewTransformer);

        $this->type->buildForm($builder, $options);
    }

    public function multipleTypeDataProvider()
    {
        return [
            [true],
            [false]
        ];
    }

    public function testCreateEntitiesToIdsTransformerMultiple()
    {
        $options = [
            'multiple' => true,
            'em' => $this->getMockEntityManager(),
            'class' => 'TestClass',
            'property' => 'id',
            'queryBuilder' => function ($repository, array $ids) {
                return $repository->createQueryBuilder('o')->where('o.id IN (:values)')->setParameter('values', $ids);
            },
        ];
        $type = new class($this->getMockManagerRegistry()) extends EntityIdentifierType {
            public function xcreateEntitiesToIdsTransformer(array $options)
            {
                return parent::createEntitiesToIdsTransformer($options);
            }
        };
        $transformer = $type->xcreateEntitiesToIdsTransformer($options);
        static::assertInstanceOf(EntitiesToIdsTransformer::class, $transformer);
        $accessor = new class($this->getMockEntityManager(), 'anything', 'anything') extends EntitiesToIdsTransformer {
            public function getEm(EntitiesToIdsTransformer $transformer): EntityManager
            {
                return $transformer->em;
            }

            public function getClassName(EntitiesToIdsTransformer $transformer): string
            {
                return $transformer->className;
            }

            public function getProperty(EntitiesToIdsTransformer $transformer): string
            {
                return $transformer->property;
            }

            public function getQueryBuilderCallback(EntitiesToIdsTransformer $transformer): callable
            {
                return $transformer->queryBuilderCallback;
            }
        };
        static::assertSame($options['em'], $accessor->getEm($transformer));
        static::assertSame($options['class'], $accessor->getClassName($transformer));
        static::assertSame($options['property'], $accessor->getProperty($transformer));
        static::assertSame($options['queryBuilder'], $accessor->getQueryBuilderCallback($transformer));
    }

    public function testCreateEntitiesToIdsTransformerNotMultiple()
    {
        $options = [
            'multiple' => false,
            'em' => $this->getMockEntityManager(),
            'class' => 'TestClass',
            'property' => 'id',
            'queryBuilder' => function ($repository, array $ids) {
                return $repository->createQueryBuilder('o')->where('o.id IN (:values)')->setParameter('values', $ids);
            },
        ];
        $type = new class($this->getMockManagerRegistry()) extends EntityIdentifierType {
            public function xcreateEntitiesToIdsTransformer(array $options)
            {
                return parent::createEntitiesToIdsTransformer($options);
            }
        };
        $transformer = $type->xcreateEntitiesToIdsTransformer($options);
        static::assertInstanceOf(EntityToIdTransformer::class, $transformer);
        static::assertNotInstanceOf(EntitiesToIdsTransformer::class, $transformer);

        $accessor = new class($this->getMockEntityManager(), 'anything', 'anything') extends EntityToIdTransformer {
            public function getEm(EntityToIdTransformer $transformer): EntityManager
            {
                return $transformer->em;
            }

            public function getClassName(EntityToIdTransformer $transformer): string
            {
                return $transformer->className;
            }

            public function getProperty(EntityToIdTransformer $transformer): string
            {
                return $transformer->property;
            }

            public function getQueryBuilderCallback(EntityToIdTransformer $transformer): callable
            {
                return $transformer->queryBuilderCallback;
            }
        };
        static::assertSame($options['em'], $accessor->getEm($transformer));
        static::assertSame($options['class'], $accessor->getClassName($transformer));
        static::assertSame($options['property'], $accessor->getProperty($transformer));
        static::assertSame($options['queryBuilder'], $accessor->getQueryBuilderCallback($transformer));
    }

    /**
     * Create list of mocked entities by id property name and values
     *
     * @param string $property
     * @param array $values
     * @return MockObject[]
     */
    private function createMockEntityList($property, array $values)
    {
        $result = [];
        foreach ($values as $value) {
            $result[] = $this->createMockEntity($property, $value);
        }

        return $result;
    }

    /**
     * Create mock entity by id property name and value
     *
     * @param string $property
     * @param mixed $value
     * @return MockObject
     */
    private function createMockEntity($property, $value)
    {
        $getter = 'get' . ucfirst($property);
        $result = $this->createPartialMock(\stdClass::class, [$getter]);
        $result->method($getter)->willReturn($value);

        return $result;
    }

    /**
     * @param MockObject|string $mock
     * @param array $expectedCalls
     */
    private function addMockExpectedCalls($mock, array $expectedCalls)
    {
        if (is_string($mock)) {
            $mockGetter = 'getMock' . ucfirst($mock);
            $mock = $this->$mockGetter($mock);
        }
        $index = 0;
        if ($expectedCalls) {
            foreach ($expectedCalls as $expectedCall) {
                list($method, $arguments, $result) = $expectedCall;

                if (is_callable($result)) {
                    $result = call_user_func($result);
                }

                $methodExpectation = $mock->expects(static::at($index++))->method($method);
                $methodExpectation = call_user_func_array([$methodExpectation, 'with'], $arguments);
                $methodExpectation->willReturn($result);
            }
        } else {
            $mock->expects(static::never())->method(static::anything());
        }
    }

    /**
     * @return ManagerRegistry|MockObject
     */
    protected function getMockManagerRegistry()
    {
        if (!$this->managerRegistry) {
            $this->managerRegistry = $this->getMockForAbstractClass(ManagerRegistry::class);
        }

        return $this->managerRegistry;
    }

    /**
     * @return EntityManager|MockObject
     */
    protected function getMockEntityManager()
    {
        if (!$this->entityManager) {
            $this->entityManager = $this->getMockBuilder(EntityManager::class)
                ->disableOriginalConstructor()
                ->onlyMethods(['getClassMetadata', 'getRepository'])
                ->getMockForAbstractClass();
        }

        return $this->entityManager;
    }

    /**
     * @return EntitiesToIdsTransformer|MockObject
     */
    protected function getMockEntitiesToIdsTransformer()
    {
        if (!$this->entitiesToIdsTransformer) {
            $this->entitiesToIdsTransformer =
                $this->getMockBuilder(EntitiesToIdsTransformer::class)
                    ->disableOriginalConstructor()
                    ->onlyMethods(['transform', 'reverseTransform'])
                    ->getMockForAbstractClass();
        }

        return $this->entitiesToIdsTransformer;
    }
}
