<?php

namespace Oro\Bundle\DataGridBundle\Tests\Unit\Datasource\Orm;

use Doctrine\DBAL\Platforms\MySqlPlatform;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Query;
use Oro\Bundle\DataGridBundle\Datasource\Orm\Configs\YamlProcessor;
use Oro\Bundle\DataGridBundle\Datasource\Orm\OrmDatasource;
use Oro\Bundle\DataGridBundle\Datasource\ParameterBinderInterface;
use Oro\Component\DoctrineUtils\ORM\QueryHintResolver;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class OrmDatasourceTest extends \PHPUnit\Framework\TestCase
{
    /** @var OrmDatasource */
    protected $datasource;

    /** @var EntityManager|\PHPUnit\Framework\MockObject\MockObject */
    protected $em;

    /** @var YamlProcessor|\PHPUnit\Framework\MockObject\MockObject */
    protected $processor;

    /** @var EventDispatcherInterface|\PHPUnit\Framework\MockObject\MockObject */
    protected $eventDispatcher;

    /** @var ParameterBinderInterface|\PHPUnit\Framework\MockObject\MockObject */
    protected $parameterBinder;

    protected function setUp()
    {
        $this->em = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->processor = $this->getMockBuilder('Oro\Bundle\DataGridBundle\Datasource\Orm\Configs\YamlProcessor')
            ->disableOriginalConstructor()
            ->getMock();

        $this->parameterBinder = $this->createMock('Oro\\Bundle\\DataGridBundle\\Datasource\\ParameterBinderInterface');
        $this->eventDispatcher = $this->createMock('Symfony\Component\EventDispatcher\EventDispatcherInterface');
        $queryHintResolver     = new QueryHintResolver();
        $this->datasource      = new OrmDatasource(
            $this->processor,
            $this->eventDispatcher,
            $this->parameterBinder,
            $queryHintResolver
        );
    }

    /**
     * @dataProvider hintConfigProvider
     *
     * @param array|null $hints
     * @param array|null $countHints
     * @param array $expected
     * @param array $expectedCountQueryHints
     */
    public function testHints($hints, $countHints, array $expected, array $expectedCountQueryHints)
    {
        $entityClass      = 'Oro\Bundle\DataGridBundle\Tests\Unit\DataFixtures\Stub\SomeClass';
        $configs['query'] = [
            'select' => ['t'],
            'from'   => [
                ['table' => 'Test', 'alias' => 't']
            ]
        ];
        if (null !== $hints) {
            $configs['hints'] = $hints;
        }
        if (null !== $countHints) {
            $configs['count_hints'] = $countHints;
        }

        $this->prepareEntityManagerForTestHints($entityClass);

        $query = new Query($this->em);
        $query->setDQL("SELECT t FROM $entityClass t");
        $this->em->expects($this->any())
            ->method('getClassMetadata')
            ->with($entityClass)
            ->will($this->returnValue(new ClassMetadata($entityClass)));

        $qb = $this->getMockBuilder('Doctrine\ORM\QueryBuilder')
            ->setConstructorArgs([$this->em])
            ->getMock();

        $qb->expects($this->once())
            ->method('getQuery')
            ->will($this->returnValue($query));
        $this->processor
            ->expects($this->once())
            ->method('processQuery')
            ->willReturn($qb);
        $datagrid = $this->createMock('Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface');
        $this->datasource->process($datagrid, $configs);
        $this->datasource->getResults();

        $this->assertEquals(
            $expected,
            $query->getHints()
        );
        $this->assertEquals(
            $expectedCountQueryHints,
            $this->datasource->getCountQueryHints()
        );
    }

    protected function prepareEntityManagerForTestHints()
    {
        $connection = $this->getMockBuilder('Doctrine\DBAL\Connection')
            ->disableOriginalConstructor()
            ->getMock();
        $connection->expects($this->any())
            ->method('getDatabasePlatform')
            ->will($this->returnValue(new MySqlPlatform()));
        $connection->expects($this->any())
            ->method('getParams')
            ->will($this->returnValue([]));
        $this->em->expects($this->any())
            ->method('getConnection')
            ->will($this->returnValue($connection));
        $hydrator = $this->getMockBuilder('Doctrine\ORM\Internal\Hydration\SimpleObjectHydrator')
            ->disableOriginalConstructor()
            ->getMock();
        $hydrator->expects($this->once())
            ->method('hydrateAll')
            ->will($this->returnValue([]));
        $this->em->expects($this->once())
            ->method('newHydrator')
            ->will($this->returnValue($hydrator));

        $configuration = new Configuration();
        $this->em->expects($this->any())
            ->method('getConfiguration')
            ->will($this->returnValue($configuration));
    }

    /**
     * @return array
     */
    public function hintConfigProvider()
    {
        return [
            [
                null,
                null,
                [],
                []
            ],
            [
                [],
                null,
                [],
                []
            ],
            [
                [],
                [],
                [],
                []
            ],
            [
                [
                    ['name' => 'HINT_FORCE_PARTIAL_LOAD', 'value' => true]
                ],
                null,
                [
                    Query::HINT_FORCE_PARTIAL_LOAD => true
                ],
                [
                    ['name' => 'HINT_FORCE_PARTIAL_LOAD', 'value' => true]
                ]
            ],
            [
                [
                    ['name' => 'HINT_FORCE_PARTIAL_LOAD', 'value' => true]
                ],
                [],
                [
                    Query::HINT_FORCE_PARTIAL_LOAD => true
                ],
                []
            ],
            [
                [
                    ['name' => 'HINT_FORCE_PARTIAL_LOAD', 'value' => false]
                ],
                null,
                [
                    Query::HINT_FORCE_PARTIAL_LOAD => false
                ],
                [
                    ['name' => 'HINT_FORCE_PARTIAL_LOAD', 'value' => false]
                ]
            ],
            [
                [
                    ['name' => 'HINT_FORCE_PARTIAL_LOAD']
                ],
                null,
                [
                    Query::HINT_FORCE_PARTIAL_LOAD => true
                ],
                [
                    ['name' => 'HINT_FORCE_PARTIAL_LOAD']
                ]
            ],
            [
                ['HINT_FORCE_PARTIAL_LOAD'],
                null,
                [Query::HINT_FORCE_PARTIAL_LOAD => true],
                ['HINT_FORCE_PARTIAL_LOAD']
            ],
            [
                [
                    ['name' => 'some_custom_hint', 'value' => 'test_val']
                ],
                null,
                [
                    'some_custom_hint' => 'test_val'
                ],
                [
                    ['name' => 'some_custom_hint', 'value' => 'test_val']
                ],
            ],
            [
                ['some_custom_hint'],
                null,
                ['some_custom_hint' => true],
                ['some_custom_hint'],
            ],
            [
                ['some_custom_hint'],
                ['some_custom_count_hint'],
                ['some_custom_hint' => true],
                ['some_custom_count_hint'],
            ],
        ];
    }

    public function testBindParametersWorks()
    {
        $parameters = ['foo'];
        $append     = true;

        $datagrid         = $this->createMock('Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface');
        $configs['query'] = [
            'select' => ['t'],
            'from'   => [
                ['table' => 'Test', 'alias' => 't']
            ]
        ];

        $qb = $this->getMockBuilder('Doctrine\ORM\QueryBuilder')
            ->setConstructorArgs([$this->em])
            ->getMock();

        $this->parameterBinder->expects($this->once())
            ->method('bindParameters')
            ->with($datagrid, $parameters, $append);
        $this->processor
            ->expects($this->once())
            ->method('processQuery')
            ->willReturn($qb);
        $this->datasource->process($datagrid, $configs);
        $this->datasource->bindParameters($parameters, $append);
    }

    /**
     * @expectedException \BadMethodCallException
     * @expectedExceptionMessage Method is not allowed when datasource is not processed.
     */
    public function testBindParametersFailsWhenDatagridIsEmpty()
    {
        $this->datasource->bindParameters(['foo']);
    }

    public function testClone()
    {
        $qb = $this->getMockBuilder('Doctrine\ORM\QueryBuilder')
            ->setConstructorArgs([$this->em])
            ->getMock();

        $this->datasource->setQueryBuilder($qb);
        $this->datasource = clone $this->datasource;
        $this->assertNotSame($qb, $this->datasource->getQueryBuilder());
    }
}
