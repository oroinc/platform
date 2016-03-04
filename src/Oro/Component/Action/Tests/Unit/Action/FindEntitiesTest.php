<?php

namespace Oro\Component\Action\Tests\Unit\Action;

use Doctrine\Common\Persistence\ManagerRegistry;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\PropertyAccess\PropertyPath;

use Oro\Component\Action\Action\FindEntities;
use Oro\Component\Action\Model\ContextAccessor;
use Oro\Component\ConfigExpression\Tests\Unit\Fixtures\ItemStub;

class FindEntitiesTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var FindEntities
     */
    protected $function;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|ManagerRegistry
     */
    protected $registry;

    protected function setUp()
    {
        $this->registry = $this->getMock('Doctrine\Common\Persistence\ManagerRegistry');

        /** @var EventDispatcherInterface $dispatcher */
        $dispatcher = $this->getMock('Symfony\Component\EventDispatcher\EventDispatcherInterface');

        $this->function = new FindEntities(new ContextAccessor(), $this->registry);
        $this->function->setDispatcher($dispatcher);
    }

    protected function tearDown()
    {
        unset($this->registry, $this->function);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|PropertyPath
     */
    protected function getPropertyPath()
    {
        return $this->getMockBuilder('Symfony\Component\PropertyAccess\PropertyPath')
            ->disableOriginalConstructor()
            ->getMock();
    }

    /**
     * @param array $options
     * @param string $expectedMessage
     * @dataProvider initializeExceptionDataProvider
     */
    public function testInitializeException(array $options, $expectedMessage)
    {
        $this->setExpectedException(
            '\Oro\Component\Action\Exception\InvalidParameterException',
            $expectedMessage
        );

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

    /**
     * @expectedException \Oro\Component\Action\Exception\NotManageableEntityException
     * @expectedExceptionMessage Entity class "\stdClass" is not manageable.
     */
    public function testExecuteNotManageableEntity()
    {
        $this->registry->expects($this->once())
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
     * @param array $source
     * @param array $expected
     * @dataProvider initializeDataProvider
     */
    public function testInitialize(array $source, array $expected)
    {
        $this->assertEquals($this->function, $this->function->initialize($source));
        $this->assertAttributeEquals($expected, 'options', $this->function);
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

        $query = $this->getMockBuilder('Doctrine\ORM\AbstractQuery')
            ->disableOriginalConstructor()
            ->setMethods(['getResult'])
            ->getMockForAbstractClass();
        $query->expects($this->once())
            ->method('getResult')
            ->willReturn([$entity]);

        $queryBuilder = $this->getMockBuilder('Doctrine\ORM\QueryBuilder')->disableOriginalConstructor()->getMock();
        $queryBuilder->expects($this->once())
            ->method('andWhere')
            ->with('e.name = :name')
            ->willReturnSelf();
        $queryBuilder->expects($this->once())
            ->method('orWhere')
            ->with('e.label = :label')
            ->willReturnSelf();
        $queryBuilder->expects($this->once())
            ->method('setParameters')
            ->with($parameters)
            ->willReturnSelf();
        $queryBuilder->expects($this->once())
            ->method('orderBy')
            ->with('e.createdDate', $options['order_by']['createdDate'])
            ->willReturnSelf();
        $queryBuilder->expects($this->once())
            ->method('getQuery')
            ->willReturn($query);

        $repository = $this->getMockBuilder('Doctrine\ORM\EntityRepository')->disableOriginalConstructor()->getMock();
        $repository->expects($this->once())
            ->method('createQueryBuilder')
            ->with('e')
            ->willReturn($queryBuilder);

        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')->disableOriginalConstructor()->getMock();
        $em->expects($this->once())
            ->method('getRepository')
            ->with($options['class'])
            ->willReturn($repository);

        $this->registry->expects($this->once())
            ->method('getManagerForClass')
            ->with($options['class'])
            ->willReturn($em);

        $context = new ItemStub();

        $this->function->initialize($options);
        $this->function->execute($context);

        $attributeName = (string)$options['attribute'];
        $this->assertEquals([$entity], $context->$attributeName);
    }
}
