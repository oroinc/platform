<?php

namespace Oro\Component\Action\Tests\Unit\Action;

use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Component\Action\Action\RequestEntity;
use Oro\Component\Action\Exception\InvalidParameterException;
use Oro\Component\Action\Exception\NotManageableEntityException;
use Oro\Component\ConfigExpression\ContextAccessor;
use Oro\Component\ConfigExpression\Tests\Unit\Fixtures\ItemStub;
use Oro\Component\Testing\ReflectionUtil;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\PropertyAccess\PropertyPath;

class RequestEntityTest extends \PHPUnit\Framework\TestCase
{
    /** @var ContextAccessor */
    private $contextAccessor;

    /** @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject */
    private $registry;

    /** @var RequestEntity */
    private $action;

    protected function setUp(): void
    {
        $this->contextAccessor = new ContextAccessor();
        $this->registry = $this->createMock(ManagerRegistry::class);

        $this->action = new RequestEntity($this->contextAccessor, $this->registry);
        $this->action->setDispatcher($this->createMock(EventDispatcher::class));
    }

    /**
     * @dataProvider initializeExceptionDataProvider
     */
    public function testInitializeException(array $options, string $expectedMessage)
    {
        $this->expectException(InvalidParameterException::class);
        $this->expectExceptionMessage($expectedMessage);

        $this->action->initialize($options);
    }

    public function initializeExceptionDataProvider(): array
    {
        return [
            'no class name' => [
                'options' => [
                    'some' => 1,
                ],
                'message' => 'Class name parameter is required'
            ],
            'no attribute' => [
                'options' => [
                    'class' => 'stdClass',
                ],
                'message' => 'Attribute name parameter is required'
            ],
            'invalid attribute' => [
                [
                    'class' => 'stdClass',
                    'identifier' => 1,
                    'attribute' => 'string',
                ],
                'message' => 'Attribute must be valid property definition.'
            ],
            'no identifier' => [
                'options' => [
                    'class' => 'stdClass',
                    'attribute' => $this->createMock(PropertyPath::class),
                ],
                'message' => 'One of parameters "identifier", "where" or "order_by" must be defined'
            ],
            'invalid where' => [
                'options' => [
                    'class' => 'stdClass',
                    'attribute' => $this->createMock(PropertyPath::class),
                    'where' => 'scalar_data'
                ],
                'message' => 'Parameter "where" must be array'
            ],
            'invalid order_by' => [
                'options' => [
                    'class' => 'stdClass',
                    'attribute' => $this->createMock(PropertyPath::class),
                    'order_by' => 'scalar_data'
                ],
                'message' => 'Parameter "order_by" must be array'
            ],
        ];
    }

    public function testExecuteNotManageableEntity()
    {
        $this->expectException(NotManageableEntityException::class);
        $this->expectExceptionMessage('Entity class "stdClass" is not manageable.');

        $options = [
            'class' => \stdClass::class,
            'identifier' => 1,
            'attribute' => $this->createMock(PropertyPath::class)
        ];
        $context = new ItemStub([]);

        $this->registry->expects(self::once())
            ->method('getManagerForClass')
            ->with(\stdClass::class)
            ->willReturn(null);

        $this->action->initialize($options);
        $this->action->execute($context);
    }

    /**
     * @dataProvider initializeDataProvider
     */
    public function testInitialize(array $source, array $expected)
    {
        self::assertSame($this->action, $this->action->initialize($source));
        self::assertEquals($expected, ReflectionUtil::getPropertyValue($this->action, 'options'));
    }

    public function initializeDataProvider(): array
    {
        return [
            'entity identifier' => [
                'source' => [
                    'class' => 'stdClass',
                    'identifier' => 1,
                    'attribute' => $this->createMock(PropertyPath::class),
                ],
                'expected' => [
                    'class' => 'stdClass',
                    'identifier' => 1,
                    'attribute' => $this->createMock(PropertyPath::class),
                    'where' => [],
                    'order_by' => [],
                    'case_insensitive' => false,
                ],
            ],
            'where and order by' => [
                'source' => [
                    'class' => 'stdClass',
                    'where' => ['name' => 'qwerty'],
                    'order_by' => ['date' => 'asc'],
                    'attribute' => $this->createMock(PropertyPath::class),
                    'case_insensitive' => true,
                ],
                'expected' => [
                    'class' => 'stdClass',
                    'where' => ['name' => 'qwerty'],
                    'order_by' => ['date' => 'asc'],
                    'attribute' => $this->createMock(PropertyPath::class),
                    'case_insensitive' => true,
                ],
            ]
        ];
    }

    /**
     * @dataProvider executeDataProvider
     */
    public function testExecute(array $options, array $data = [])
    {
        $context = new ItemStub($data);
        $entity = new \stdClass();

        if (is_string($options['identifier'])) {
            $options['identifier'] = trim($options['identifier']);
        }

        $expectedIdentifier = $this->processAttribute($context, $options['identifier']);
        $expectedClass = $this->processAttribute($context, $options['class']);
        if (!empty($options['case_insensitive'])) {
            $expectedIdentifier = strtolower($expectedIdentifier);
        }

        $em = $this->createMock(EntityManager::class);
        $em->expects($this->once())
            ->method('getReference')
            ->with($expectedClass, $expectedIdentifier)
            ->willReturn($entity);

        $this->registry->expects($this->once())
            ->method('getManagerForClass')
            ->with($expectedClass)
            ->willReturn($em);

        $this->action->initialize($options);
        $this->action->execute($context);

        $attributeName = (string)$options['attribute'];
        $this->assertEquals($entity, $context->{$attributeName});
    }

    public function executeDataProvider(): array
    {
        return [
            'scalar_identifier' => [
                'options' => [
                    'class' => \stdClass::class,
                    'identifier' => 1,
                    'attribute' => new PropertyPath('entity_attribute'),
                ]
            ],
            'attribute_class' => [
                'options' => [
                    'class' => new PropertyPath('class'),
                    'identifier' => 1,
                    'attribute' => new PropertyPath('entity_attribute'),
                ]
            ],
            'scalar_attribute_identifier' => [
                'options' => [
                    'class' => \stdClass::class,
                    'identifier' => new PropertyPath('id'),
                    'attribute' => new PropertyPath('entity_attribute'),
                ],
                'data' => [
                    'id' => 1
                ],
            ],
            'scalar_case_insensitive_identifier' => [
                'options' => [
                    'class' => \stdClass::class,
                    'identifier' => ' DATA ',
                    'attribute' => new PropertyPath('entity_attribute'),
                    'case_insensitive' => true,
                ]
            ],
            'array_identifier' => [
                'options' => [
                    'class' => \stdClass::class,
                    'identifier' => [
                        'id'   => 1,
                        'name' => 'unique_key',
                    ],
                    'attribute' => new PropertyPath('entity_attribute'),
                ]
            ],
            'property_identifier' => [
                'options' => [
                    'class' => \stdClass::class,
                    'identifier' => new PropertyPath('ident'),
                    'attribute' => new PropertyPath('entity_attribute'),
                ],
                'data' => [
                    'ident' => [
                        'id'   => 1,
                        'name' => 'unique_key',
                    ],
                ],
            ],
            'array_attribute_identifier' => [
                'options' => [
                    'class' => \stdClass::class,
                    'identifier' => [
                        'id'   => new PropertyPath('id_attribute'),
                        'name' => new PropertyPath('name_attribute'),
                    ],
                    'attribute' => new PropertyPath('entity_attribute'),
                ],
                'data' => [
                    'id_attribute'   => 1,
                    'name_attribute' => 'unique_key',
                ],
            ],
        ];
    }

    /**
     * @dataProvider executeWithConditionsDataProvider
     */
    public function testExecuteWithWhereAndOrderBy(bool $caseInsensitive)
    {
        $options = [
            'class' => \stdClass::class,
            'where' => ['name' => ' Qwerty '],
            'attribute' => new PropertyPath('entity'),
            'order_by' => ['createdDate' => ' asc '],
            'case_insensitive' => $caseInsensitive
        ];

        $context = new ItemStub();
        $entity = new \stdClass();

        $query = $this->createMock(AbstractQuery::class);
        $query->expects($this->once())
            ->method('getOneOrNullResult')
            ->willReturn($entity);

        $expectedField = !empty($options['case_insensitive']) ? 'LOWER(e.name)' : 'e.name';
        $expectedValue = !empty($options['case_insensitive'])
            ? trim(strtolower($options['where']['name']))
            : trim($options['where']['name']);
        $expectedParameter = 'parameter_0';
        $expectedOrder = 'e.createdDate';

        $queryBuilder = $this->createMock(QueryBuilder::class);
        $queryBuilder->expects($this->once())
            ->method('andWhere')
            ->with("$expectedField = :$expectedParameter")
            ->willReturnSelf();
        $queryBuilder->expects($this->once())
            ->method('setParameter')
            ->with($expectedParameter, $expectedValue)
            ->willReturnSelf();
        $queryBuilder->expects($this->once())
            ->method('orderBy')
            ->with($expectedOrder, strtoupper(trim($options['order_by']['createdDate'])))
            ->willReturnSelf();
        $queryBuilder->expects($this->once())
            ->method('getQuery')
            ->willReturn($query);
        $queryBuilder->expects($this->once())
            ->method('setMaxResults')
            ->with(1);

        $repository = $this->createMock(EntityRepository::class);
        $repository->expects($this->once())
            ->method('createQueryBuilder')
            ->with('e')
            ->willReturn($queryBuilder);

        $em = $this->createMock(EntityManager::class);
        $em->expects($this->once())
            ->method('getRepository')
            ->with($options['class'])
            ->willReturn($repository);

        $this->registry->expects($this->once())
            ->method('getManagerForClass')
            ->with($options['class'])
            ->willReturn($em);

        $this->action->initialize($options);
        $this->action->execute($context);

        $attributeName = (string)$options['attribute'];
        $this->assertEquals($entity, $context->{$attributeName});
    }

    public function executeWithConditionsDataProvider(): array
    {
        return [
            'case sensitive' => [
                'caseInsensitive' => false,
            ],
            'case insensitive' => [
                'caseInsensitive' => true,
            ],
        ];
    }

    private function processAttribute(mixed $context, mixed $identifier): mixed
    {
        if (is_array($identifier)) {
            foreach ($identifier as $key => $value) {
                $identifier[$key] = $this->contextAccessor->getValue($context, $value);
            }
        } else {
            $identifier = $this->contextAccessor->getValue($context, $identifier);
        }

        return $identifier;
    }
}
