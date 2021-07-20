<?php

namespace Oro\Component\Action\Tests\Unit\Action;

use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Component\Action\Action\FindEntities;
use Oro\Component\Action\Exception\InvalidParameterException;
use Oro\Component\Action\Exception\NotManageableEntityException;
use Oro\Component\ConfigExpression\ContextAccessor;
use Oro\Component\ConfigExpression\Tests\Unit\Fixtures\ItemStub;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\PropertyAccess\PropertyPath;

class FindEntitiesTest extends \PHPUnit\Framework\TestCase
{
    /** @var FindEntities */
    protected $function;

    /** @var MockObject|ManagerRegistry */
    protected $registry;

    protected function setUp(): void
    {
        $this->registry = $this->createMock(ManagerRegistry::class);

        /** @var EventDispatcherInterface $dispatcher */
        $dispatcher = $this->createMock(EventDispatcherInterface::class);

        $this->function = new class(new ContextAccessor(), $this->registry) extends FindEntities {
            public function xgetOptions(): array
            {
                return $this->options;
            }
        };
        $this->function->setDispatcher($dispatcher);
    }

    protected function tearDown(): void
    {
        unset($this->registry, $this->function);
    }

    /**
     * @return MockObject|PropertyPath
     */
    protected function getPropertyPath()
    {
        return $this->getMockBuilder(PropertyPath::class)->disableOriginalConstructor()->getMock();
    }

    /**
     * @param array $options
     * @param string $expectedMessage
     * @dataProvider initializeExceptionDataProvider
     */
    public function testInitializeException(array $options, $expectedMessage)
    {
        $this->expectException(InvalidParameterException::class);
        $this->expectExceptionMessage($expectedMessage);

        $this->function->initialize($options);
    }

    /**
     * @return array
     */
    public function initializeExceptionDataProvider()
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
                    'attribute' => 'string',
                ],
                'message' => 'Attribute must be valid property definition.'
            ],
            'no where or order_by' => [
                'options' => [
                    'class' => 'stdClass',
                    'attribute' => $this->getPropertyPath()
                ],
                'message' => 'One of parameters "where" or "order_by" must be defined'
            ],
            'invalid where' => [
                'options' => [
                    'class' => 'stdClass',
                    'attribute' => $this->getPropertyPath(),
                    'where' => 'scalar_data'
                ],
                'message' => 'Parameter "where" must be array'
            ],
            'invalid order_by' => [
                'options' => [
                    'class' => 'stdClass',
                    'attribute' => $this->getPropertyPath(),
                    'order_by' => 'scalar_data'
                ],
                'message' => 'Parameter "order_by" must be array'
            ],
        ];
    }

    public function testExecuteNotManageableEntity()
    {
        $this->expectException(NotManageableEntityException::class);
        $this->expectExceptionMessage('Entity class "\stdClass" is not manageable.');

        $this->registry->expects(static::once())
            ->method('getManagerForClass')
            ->with('\stdClass')
            ->willReturn(null);

        $this->function->initialize(
            [
                'class' => '\stdClass',
                'attribute' => $this->getPropertyPath(),
                'where' => ['and' => []]
            ]
        );
        $this->function->execute(new ItemStub([]));
    }

    /**
     * @dataProvider initializeDataProvider
     */
    public function testInitialize(array $source, array $expected)
    {
        static::assertEquals($this->function, $this->function->initialize($source));
        static::assertEquals($expected, $this->function->xgetOptions());
    }

    /**
     * @return array
     */
    public function initializeDataProvider()
    {
        return [
            'where and order by' => [
                'source' => [
                    'class' => 'stdClass',
                    'where' => ['name' => 'qwerty'],
                    'order_by' => ['date' => 'asc'],
                    'attribute' => $this->getPropertyPath(),
                    'case_insensitive' => true,
                ],
                'expected' => [
                    'class' => 'stdClass',
                    'where' => ['name' => 'qwerty'],
                    'order_by' => ['date' => 'asc'],
                    'attribute' => $this->getPropertyPath(),
                    'case_insensitive' => true,
                ],
            ]
        ];
    }

    public function testExecute()
    {
        $parameters = ['name' => 'Test Name'];

        $options = [
            'class' => '\stdClass',
            'where' => [
                'and' => ['e.name = :name'],
                'or' => ['e.label = :label']
            ],
            'attribute' => new PropertyPath('entities'),
            'order_by' => ['createdDate' => 'asc'],
            'query_parameters' => $parameters,
        ];

        $entity = new \stdClass();

        $query = $this->getMockBuilder(AbstractQuery::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getResult'])
            ->getMockForAbstractClass();
        $query->expects(static::once())
            ->method('getResult')
            ->willReturn([$entity]);

        $queryBuilder = $this->getMockBuilder(QueryBuilder::class)->disableOriginalConstructor()->getMock();
        $queryBuilder->expects(static::once())
            ->method('andWhere')
            ->with('e.name = :name')
            ->willReturnSelf();
        $queryBuilder->expects(static::once())
            ->method('orWhere')
            ->with('e.label = :label')
            ->willReturnSelf();
        $queryBuilder->expects(static::once())
            ->method('setParameters')
            ->with($parameters)
            ->willReturnSelf();
        $queryBuilder->expects(static::once())
            ->method('orderBy')
            ->with('e.createdDate', strtoupper($options['order_by']['createdDate']))
            ->willReturnSelf();
        $queryBuilder->expects(static::once())
            ->method('getQuery')
            ->willReturn($query);

        $repository = $this->getMockBuilder(EntityRepository::class)->disableOriginalConstructor()->getMock();
        $repository->expects(static::once())
            ->method('createQueryBuilder')
            ->with('e')
            ->willReturn($queryBuilder);

        $em = $this->getMockBuilder(EntityManager::class)->disableOriginalConstructor()->getMock();
        $em->expects(static::once())
            ->method('getRepository')
            ->with($options['class'])
            ->willReturn($repository);

        $this->registry->expects(static::once())
            ->method('getManagerForClass')
            ->with($options['class'])
            ->willReturn($em);

        $context = new ItemStub();

        $this->function->initialize($options);
        $this->function->execute($context);

        $attributeName = (string)$options['attribute'];
        static::assertEquals([$entity], $context->$attributeName);
    }
}
