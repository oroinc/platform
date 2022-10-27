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
use Oro\Component\Testing\ReflectionUtil;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\PropertyAccess\PropertyPath;

class FindEntitiesTest extends \PHPUnit\Framework\TestCase
{
    /** @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject */
    private $registry;

    /** @var FindEntities */
    private $action;

    protected function setUp(): void
    {
        $this->registry = $this->createMock(ManagerRegistry::class);

        $this->action = new FindEntities(new ContextAccessor(), $this->registry);
        $this->action->setDispatcher($this->createMock(EventDispatcherInterface::class));
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
                    'attribute' => 'string',
                ],
                'message' => 'Attribute must be valid property definition.'
            ],
            'no where or order_by' => [
                'options' => [
                    'class' => 'stdClass',
                    'attribute' => $this->createMock(PropertyPath::class)
                ],
                'message' => 'One of parameters "where" or "order_by" must be defined'
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
        $this->expectExceptionMessage(sprintf('Entity class "%s" is not manageable.', \stdClass::class));

        $this->registry->expects(self::once())
            ->method('getManagerForClass')
            ->with(\stdClass::class)
            ->willReturn(null);

        $this->action->initialize(
            [
                'class' => \stdClass::class,
                'attribute' => $this->createMock(PropertyPath::class),
                'where' => ['and' => []]
            ]
        );
        $this->action->execute(new ItemStub([]));
    }

    /**
     * @dataProvider initializeDataProvider
     */
    public function testInitialize(array $source, array $expected)
    {
        self::assertEquals($this->action, $this->action->initialize($source));
        self::assertEquals($expected, ReflectionUtil::getPropertyValue($this->action, 'options'));
    }

    public function initializeDataProvider(): array
    {
        return [
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

    public function testExecute()
    {
        $parameters = ['name' => 'Test Name'];

        $options = [
            'class' => \stdClass::class,
            'where' => [
                'and' => ['e.name = :name'],
                'or' => ['e.label = :label']
            ],
            'attribute' => new PropertyPath('entities'),
            'order_by' => ['createdDate' => 'asc'],
            'query_parameters' => $parameters,
        ];

        $entity = new \stdClass();

        $query = $this->createMock(AbstractQuery::class);
        $query->expects(self::once())
            ->method('getResult')
            ->willReturn([$entity]);

        $queryBuilder = $this->createMock(QueryBuilder::class);
        $queryBuilder->expects(self::once())
            ->method('andWhere')
            ->with('e.name = :name')
            ->willReturnSelf();
        $queryBuilder->expects(self::once())
            ->method('orWhere')
            ->with('e.label = :label')
            ->willReturnSelf();
        $queryBuilder->expects(self::once())
            ->method('setParameters')
            ->with($parameters)
            ->willReturnSelf();
        $queryBuilder->expects(self::once())
            ->method('orderBy')
            ->with('e.createdDate', strtoupper($options['order_by']['createdDate']))
            ->willReturnSelf();
        $queryBuilder->expects(self::once())
            ->method('getQuery')
            ->willReturn($query);

        $repository = $this->createMock(EntityRepository::class);
        $repository->expects(self::once())
            ->method('createQueryBuilder')
            ->with('e')
            ->willReturn($queryBuilder);

        $em = $this->createMock(EntityManager::class);
        $em->expects(self::once())
            ->method('getRepository')
            ->with($options['class'])
            ->willReturn($repository);

        $this->registry->expects(self::once())
            ->method('getManagerForClass')
            ->with($options['class'])
            ->willReturn($em);

        $context = new ItemStub();

        $this->action->initialize($options);
        $this->action->execute($context);

        $attributeName = (string)$options['attribute'];
        self::assertEquals([$entity], $context->{$attributeName});
    }
}
