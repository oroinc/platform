<?php

namespace Oro\Bundle\DataGridBundle\Tests\Unit\Datasource\Orm;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Query\Parameter;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface;
use Oro\Bundle\DataGridBundle\Datagrid\ParameterBag;
use Oro\Bundle\DataGridBundle\Datasource\Orm\OrmDatasource;
use Oro\Bundle\DataGridBundle\Datasource\Orm\ParameterBinder;
use Oro\Bundle\DataGridBundle\Exception\InvalidArgumentException;

class ParameterBinderTest extends \PHPUnit\Framework\TestCase
{
    /** @var DatagridInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $datagrid;

    /** @var OrmDatasource|\PHPUnit\Framework\MockObject\MockObject */
    private $datasource;

    /** @var QueryBuilder|\PHPUnit\Framework\MockObject\MockObject */
    private $queryBuilder;

    /** @var ParameterBinder */
    private $parameterBinder;

    protected function setUp(): void
    {
        $this->datagrid = $this->createMock(DatagridInterface::class);
        $this->datasource = $this->createMock(OrmDatasource::class);
        $this->queryBuilder = $this->createMock(QueryBuilder::class);

        $this->parameterBinder = new ParameterBinder();
    }

    /**
     * @dataProvider bindParametersDataProvider
     */
    public function testBindParametersWorks(
        array $bindParameters,
        array $datagridParameters,
        array $oldQueryParameters,
        array $expectedQueryParameters,
        bool $append = true
    ) {
        $queryParameters = new ArrayCollection($oldQueryParameters);

        $this->datagrid->expects($this->once())
            ->method('getDatasource')
            ->willReturn($this->datasource);

        $this->datasource->expects($this->once())
            ->method('getQueryBuilder')
            ->willReturn($this->queryBuilder);

        $this->queryBuilder->expects($this->once())
            ->method('getParameters')
            ->willReturn($queryParameters);

        $this->datagrid->expects($this->once())
            ->method('getParameters')
            ->willReturn(new ParameterBag($datagridParameters));

        $this->parameterBinder->bindParameters($this->datagrid, $bindParameters, $append);

        $this->assertEquals(
            $expectedQueryParameters,
            $queryParameters->toArray()
        );
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function bindParametersDataProvider(): array
    {
        return [
            'short format' => [
                'bindParameters' => [
                    'entity_id',
                    'entity_name',
                ],
                'datagridParameters' => [
                    'entity_id' => 1,
                    'entity_name' => 'test',
                ],
                'oldQueryParameters' => [],
                'expectedQueryParameters' => [
                    new Parameter('entity_id', 1),
                    new Parameter('entity_name', 'test'),
                ]
            ],
            'key format' => [
                'bindParameters' => [
                    'entity_id' => 'entityId',
                    'entity_name' => 'entityName',
                ],
                'datagridParameters' => [
                    'entityId' => 1,
                    'entityName' => 'test',
                ],
                'oldQueryParameters' => [],
                'expectedQueryParameters' => [
                    new Parameter('entity_id', 1),
                    new Parameter('entity_name', 'test'),
                ]
            ],
            'full format' => [
                'bindParameters' => [
                    'entity_id' => [
                        'path' => 'entityId'
                    ],
                    [
                        'name' => 'entity_name',
                        'path' => 'entityName',
                        'type' => Types::STRING
                    ]
                ],
                'datagridParameters' => [
                    'entityId' => 1,
                    'entityName' => 'test',
                ],
                'oldQueryParameters' => [],
                'expectedQueryParameters' => [
                    new Parameter('entity_id', 1),
                    new Parameter('entity_name', 'test', Types::STRING),
                ]
            ],
            'default value' => [
                'bindParameters' => [
                    'entity_name' => [
                        'default' => 'test',
                    ]
                ],
                'datagridParameters' => [],
                'oldQueryParameters' => [],
                'expectedQueryParameters' => [
                    new Parameter('entity_name', 'test'),
                ]
            ],
            'default empty array' => [
                'bindParameters' => [
                    'entity_name' => [
                        'default' => [0],
                    ]
                ],
                'datagridParameters' => [
                    'entity_name' => []
                ],
                'oldQueryParameters' => [],
                'expectedQueryParameters' => [
                    new Parameter('entity_name', [0]),
                ]
            ],
            'default array with empty string' => [
                'bindParameters' => [
                    'entity_name' => [
                        'default' => [0],
                    ]
                ],
                'datagridParameters' => [
                    'entity_name' => ['']
                ],
                'oldQueryParameters' => [],
                'expectedQueryParameters' => [
                    new Parameter('entity_name', [0]),
                ]
            ],
            'default catch exception' => [
                'bindParameters' => [
                    'entity_name' => [
                        'path' => 'foo.bar',
                        'default' => 'test',
                    ]
                ],
                'datagridParameters' => [
                    'foo' => new \stdClass,
                ],
                'oldQueryParameters' => [],
                'expectedQueryParameters' => [
                    new Parameter('entity_name', 'test'),
                ]
            ],
            'parameter path' => [
                'bindParameters' => [
                    'entity_id' => '_parameters.entityId',
                    'entity_name' => '_parameters.additional.entityName',
                ],
                'datagridParameters' => [
                    '_parameters' => [
                        'entityId' => 1,
                        'additional' => ['entityName' => 'test']
                    ]
                ],
                'oldQueryParameters' => [],
                'expectedQueryParameters' => [
                    new Parameter('entity_id', 1),
                    new Parameter('entity_name', 'test'),
                ]
            ],
            'append' => [
                'bindParameters' => ['entity_name'],
                'datagridParameters' => ['entity_name' => 'test'],
                'oldQueryParameters' => [new Parameter('entity_id', 1)],
                'expectedQueryParameters' => [
                    new Parameter('entity_id', 1),
                    new Parameter('entity_name', 'test'),
                ]
            ],
            'replace' => [
                'bindParameters' => ['entity_id', 'entity_name'],
                'datagridParameters' => ['entity_id' => 1, 'entity_name' => 'test'],
                'oldQueryParameters' => [new Parameter('entity_name', 'old')],
                'expectedQueryParameters' => [
                    1 => new Parameter('entity_id', 1),
                    new Parameter('entity_name', 'test'),
                ]
            ],
            'clear' => [
                'bindParameters' => ['entity_name'],
                'datagridParameters' => ['entity_name' => 'test'],
                'oldQueryParameters' => [new Parameter('entity_id', 1)],
                'expectedQueryParameters' => [
                    new Parameter('entity_name', 'test'),
                ],
                'append' => false,
            ],
        ];
    }

    public function testBindParametersFailsWithInvalidPath()
    {
        $datasource = $this->createMock(DatagridInterface::class);

        $this->datagrid->expects($this->once())
            ->method('getDatasource')
            ->willReturn($datasource);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Datagrid datasource has unexpected type "' . get_class($datasource) . '", ' .
            '"Oro\Bundle\DataGridBundle\Datasource\Orm\OrmDatasource" is expected.'
        );

        $this->parameterBinder->bindParameters($this->datagrid, ['foo']);
    }

    public function testBindParametersFailsWithInvalidDatasource()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Cannot bind datasource parameter "bar", there is no datagrid parameter with path "foo.bar".'
        );

        $datagridParameters = ['foo' => new \stdClass];
        $queryParameters = new ArrayCollection();

        $this->datagrid->expects($this->once())
            ->method('getDatasource')
            ->willReturn($this->datasource);

        $this->datasource->expects($this->once())
            ->method('getQueryBuilder')
            ->willReturn($this->queryBuilder);

        $this->queryBuilder->expects($this->once())
            ->method('getParameters')
            ->willReturn($queryParameters);

        $this->datagrid->expects($this->once())
            ->method('getParameters')
            ->willReturn(new ParameterBag($datagridParameters));

        $this->parameterBinder->bindParameters($this->datagrid, ['bar' => 'foo.bar']);
    }

    public function testBindParametersWorksWithEmptyParameters()
    {
        $this->datagrid->expects($this->never())
            ->method($this->anything());

        $this->parameterBinder->bindParameters($this->datagrid, []);
    }

    public function testBindParametersFailsWithInvalidParameter()
    {
        $queryParameters = new ArrayCollection();

        $this->datagrid->expects($this->once())
            ->method('getDatasource')
            ->willReturn($this->datasource);

        $this->datasource->expects($this->once())
            ->method('getQueryBuilder')
            ->willReturn($this->queryBuilder);

        $this->queryBuilder->expects($this->once())
            ->method('getParameters')
            ->willReturn($queryParameters);

        $this->datagrid->expects($this->once())
            ->method('getParameters')
            ->willReturn(new ParameterBag());

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Cannot bind parameter to data source, expected bind parameter format is a string or array with ' .
            'required "name" key, actual array keys are "foo"'
        );

        $this->parameterBinder->bindParameters($this->datagrid, [['foo' => 'bar']]);
    }
}
