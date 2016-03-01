<?php

namespace Oro\Bundle\DataGridBundle\Tests\Unit\Datasource\Orm;

use Doctrine\ORM\Query;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\DBAL\Platforms\MySqlPlatform;

use Oro\Component\DoctrineUtils\ORM\QueryHintResolver;

use Oro\Bundle\DataGridBundle\Datasource\Orm\OrmDatasource;

class OrmDatasourceTest extends \PHPUnit_Framework_TestCase
{
    /** @var OrmDatasource */
    protected $datasource;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $em;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $doctrine;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $eventDispatcher;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $parameterBinder;

    protected function setUp()
    {
        $this->em = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->doctrine = $this->getMockBuilder('Doctrine\Common\Persistence\ManagerRegistry')
            ->disableOriginalConstructor()
            ->getMock();

        $this->parameterBinder = $this->getMock('Oro\\Bundle\\DataGridBundle\\Datasource\\ParameterBinderInterface');
        $this->eventDispatcher = $this->getMock('Symfony\Component\EventDispatcher\EventDispatcherInterface');
        $queryHintResolver     = new QueryHintResolver();
        $this->datasource      = new OrmDatasource(
            $this->doctrine,
            $this->eventDispatcher,
            $this->parameterBinder,
            $queryHintResolver
        );
    }

    /**
     * @dataProvider hintConfigProvider
     */
    public function testHints($hints, $expected)
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

        $this->doctrine->expects($this->once())
            ->method('getManagerForClass')
            ->with('Test')
            ->willReturn($this->em);

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
        $this->em->expects($this->once())
            ->method('createQueryBuilder')
            ->will($this->returnValue($qb));
        $qb->expects($this->once())
            ->method('getQuery')
            ->will($this->returnValue($query));

        $datagrid = $this->getMock('Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface');
        $this->datasource->process($datagrid, $configs);
        $this->datasource->getResults();

        $this->assertEquals(
            $expected,
            $query->getHints()
        );
    }

    protected function prepareEntityManagerForTestHints($entityClass)
    {
        $connection = $this->getMockBuilder('Doctrine\DBAL\Connection')
            ->disableOriginalConstructor()
            ->getMock();
        $connection->expects($this->any())
            ->method('getDatabasePlatform')
            ->will($this->returnValue(new MySqlPlatform()));
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

    public function hintConfigProvider()
    {
        return [
            [
                null,
                []
            ],
            [
                [],
                []
            ],
            [
                [
                    ['name' => 'HINT_FORCE_PARTIAL_LOAD', 'value' => true]
                ],
                [
                    Query::HINT_FORCE_PARTIAL_LOAD => true
                ]
            ],
            [
                [
                    ['name' => 'HINT_FORCE_PARTIAL_LOAD', 'value' => false]
                ],
                [
                    Query::HINT_FORCE_PARTIAL_LOAD => false
                ]
            ],
            [
                [
                    ['name' => 'HINT_FORCE_PARTIAL_LOAD']
                ],
                [
                    Query::HINT_FORCE_PARTIAL_LOAD => true
                ]
            ],
            [
                [
                    'HINT_FORCE_PARTIAL_LOAD'
                ],
                [
                    Query::HINT_FORCE_PARTIAL_LOAD => true
                ]
            ],
            [
                [
                    ['name' => 'some_custom_hint', 'value' => 'test_val']
                ],
                [
                    'some_custom_hint' => 'test_val'
                ]
            ],
            [
                [
                    'some_custom_hint'
                ],
                [
                    'some_custom_hint' => true
                ]
            ],
        ];
    }

    public function testBindParametersWorks()
    {
        $parameters = ['foo'];
        $append     = true;

        $datagrid         = $this->getMock('Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface');
        $configs['query'] = [
            'select' => ['t'],
            'from'   => [
                ['table' => 'Test', 'alias' => 't']
            ]
        ];

        $this->doctrine->expects($this->once())
            ->method('getManagerForClass')
            ->with('Test')
            ->willReturn($this->em);

        $qb = $this->getMockBuilder('Doctrine\ORM\QueryBuilder')
            ->setConstructorArgs([$this->em])
            ->getMock();
        $this->em->expects($this->once())
            ->method('createQueryBuilder')
            ->will($this->returnValue($qb));

        $this->parameterBinder->expects($this->once())
            ->method('bindParameters')
            ->with($datagrid, $parameters, $append);

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
