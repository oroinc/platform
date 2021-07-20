<?php

namespace Oro\Component\Action\Tests\Unit\Action;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Component\Action\Action\RequestEntity;
use Oro\Component\Action\Exception\InvalidParameterException;
use Oro\Component\Action\Exception\NotManageableEntityException;
use Oro\Component\ConfigExpression\ContextAccessor;
use Oro\Component\ConfigExpression\Tests\Unit\Fixtures\ItemStub;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\PropertyAccess\PropertyPath;

class RequestEntityTest extends \PHPUnit\Framework\TestCase
{
    const PROPERTY_PATH_VALUE = 'property_path_value';

    /** @var RequestEntity */
    protected $action;

    /** @var ContextAccessor */
    protected $contextAccessor;

    /** @var MockObject|ManagerRegistry */
    protected $registry;

    protected function setUp(): void
    {
        $this->contextAccessor = new ContextAccessor();
        $this->registry = $this->getMockBuilder(ManagerRegistry::class)->disableOriginalConstructor()->getMock();

        $this->action = new class($this->contextAccessor, $this->registry) extends RequestEntity {
            public function xgetOptions(): array
            {
                return $this->options;
            }
        };

        /** @var EventDispatcher $dispatcher */
        $dispatcher = $this->getMockBuilder(EventDispatcher::class)->disableOriginalConstructor()->getMock();
        $this->action->setDispatcher($dispatcher);
    }

    protected function tearDown(): void
    {
        unset($this->contextAccessor, $this->registry, $this->action);
    }

    /**
     * @return PropertyPath|MockObject
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

        $this->action->initialize($options);
    }

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
                    'identifier' => 1,
                    'attribute' => 'string',
                ],
                'message' => 'Attribute must be valid property definition.'
            ],
            'no identifier' => [
                'options' => [
                    'class' => 'stdClass',
                    'attribute' => $this->getPropertyPath(),
                ],
                'message' => 'One of parameters "identifier", "where" or "order_by" must be defined'
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

        $options = [
            'class' => '\stdClass',
            'identifier' => 1,
            'attribute' => $this->getPropertyPath()
        ];
        $context = new ItemStub([]);

        $this->registry->expects(static::once())
            ->method('getManagerForClass')
            ->with('\stdClass')
            ->willReturn(null);

        $this->action->initialize($options);
        $this->action->execute($context);
    }

    /**
     * @dataProvider initializeDataProvider
     */
    public function testInitialize(array $source, array $expected)
    {
        static::assertSame($this->action, $this->action->initialize($source));
        static::assertEquals($expected, $this->action->xgetOptions());
    }

    /**
     * @return array
     */
    public function initializeDataProvider()
    {
        return [
            'entity identifier' => [
                'source' => [
                    'class' => 'stdClass',
                    'identifier' => 1,
                    'attribute' => $this->getPropertyPath(),
                ],
                'expected' => [
                    'class' => 'stdClass',
                    'identifier' => 1,
                    'attribute' => $this->getPropertyPath(),
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

    /**
     * @dataProvider executeDataProvider
     */
    public function testExecute(array $options, array $data = array())
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

        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->once())
            ->method('getReference')
            ->with($expectedClass, $expectedIdentifier)
            ->will($this->returnValue($entity));

        $this->registry->expects($this->once())
            ->method('getManagerForClass')
            ->with($expectedClass)
            ->will($this->returnValue($em));

        $this->action->initialize($options);
        $this->action->execute($context);

        $attributeName = (string)$options['attribute'];
        $this->assertEquals($entity, $context->$attributeName);
    }

    /**
     * @return array
     */
    public function executeDataProvider()
    {
        return [
            'scalar_identifier' => [
                'options' => [
                    'class' => '\stdClass',
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
                    'class' => '\stdClass',
                    'identifier' => new PropertyPath('id'),
                    'attribute' => new PropertyPath('entity_attribute'),
                ],
                'data' => [
                    'id' => 1
                ],
            ],
            'scalar_case_insensitive_identifier' => [
                'options' => [
                    'class' => '\stdClass',
                    'identifier' => ' DATA ',
                    'attribute' => new PropertyPath('entity_attribute'),
                    'case_insensitive' => true,
                ]
            ],
            'array_identifier' => [
                'options' => [
                    'class' => '\stdClass',
                    'identifier' => [
                        'id'   => 1,
                        'name' => 'unique_key',
                    ],
                    'attribute' => new PropertyPath('entity_attribute'),
                ]
            ],
            'property_identifier' => [
                'options' => [
                    'class' => '\stdClass',
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
                    'class' => '\stdClass',
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
     * @param bool $caseInsensitive
     * @dataProvider executeWithConditionsDataProvider
     */
    public function testExecuteWithWhereAndOrderBy($caseInsensitive)
    {
        $options = [
            'class' => '\stdClass',
            'where' => ['name' => ' Qwerty '],
            'attribute' => new PropertyPath('entity'),
            'order_by' => ['createdDate' => ' asc '],
            'case_insensitive' => $caseInsensitive
        ];

        $context = new ItemStub();
        $entity = new \stdClass();

        $query = $this->getMockBuilder('Doctrine\ORM\AbstractQuery')->disableOriginalConstructor()
            ->setMethods(['getOneOrNullResult'])->getMockForAbstractClass();
        $query->expects($this->once())->method('getOneOrNullResult')->will($this->returnValue($entity));

        $expectedField = !empty($options['case_insensitive']) ? 'LOWER(e.name)' : 'e.name';
        $expectedValue = !empty($options['case_insensitive'])
            ? trim(strtolower($options['where']['name']))
            : trim($options['where']['name']);
        $expectedParameter = 'parameter_0';
        $expectedOrder = 'e.createdDate';

        $queryBuilder = $this->getMockBuilder('Doctrine\ORM\QueryBuilder')->disableOriginalConstructor()->getMock();
        $queryBuilder->expects($this->once())->method('andWhere')
            ->with("$expectedField = :$expectedParameter")->will($this->returnSelf());
        $queryBuilder->expects($this->once())->method('setParameter')
            ->with($expectedParameter, $expectedValue)->will($this->returnSelf());
        $queryBuilder->expects($this->once())->method('orderBy')
            ->with($expectedOrder, strtoupper(trim($options['order_by']['createdDate'])))->will($this->returnSelf());
        $queryBuilder->expects($this->once())->method('getQuery')->will($this->returnValue($query));
        $queryBuilder->expects($this->once())->method('setMaxResults')->with($this->equalTo(1));

        $repository = $this->getMockBuilder('Doctrine\ORM\EntityRepository')->disableOriginalConstructor()->getMock();
        $repository->expects($this->once())->method('createQueryBuilder')
            ->with('e')->will($this->returnValue($queryBuilder));

        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')->disableOriginalConstructor()->getMock();
        $em->expects($this->once())->method('getRepository')
            ->with($options['class'])->will($this->returnValue($repository));

        $this->registry->expects($this->once())->method('getManagerForClass')
            ->with($options['class'])->will($this->returnValue($em));

        $this->action->initialize($options);
        $this->action->execute($context);

        $attributeName = (string)$options['attribute'];
        $this->assertEquals($entity, $context->$attributeName);
    }

    /**
     * @return array
     */
    public function executeWithConditionsDataProvider()
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

    /**
     * @param mixed $context
     * @param mixed $identifier
     * @return mixed
     */
    protected function processAttribute($context, $identifier)
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
