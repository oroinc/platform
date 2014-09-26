<?php

namespace Oro\Bundle\DataGridBundle\Tests\Unit\Datasource\Orm;

use Doctrine\Common\Collections\ArrayCollection;

use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\Query\Parameter;

use Oro\Bundle\DataGridBundle\Datagrid\ParameterBag;
use Oro\Bundle\DataGridBundle\Datasource\Orm\ParameterBinder;

class ParameterBinderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $datagrid;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $datasource;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $queryBuilder;

    /**
     * @var ParameterBinder
     */
    protected $parameterBinder;

    protected function setUp()
    {
        $this->datagrid = $this->getMock('Oro\\Bundle\\DataGridBundle\\Datagrid\\DatagridInterface');

        $this->datasource = $this->getMockBuilder('Oro\\Bundle\\DataGridBundle\\Datasource\\Orm\\OrmDatasource')
            ->disableOriginalConstructor()
            ->getMock();

        $this->queryBuilder = $this->getMockBuilder('Doctrine\\ORM\\QueryBuilder')
            ->disableOriginalConstructor()
            ->getMock();

        $this->parameterBinder = new ParameterBinder();
    }

    /**
     * @dataProvider bindParametersDataProvider
     * @param array $bindParameters
     * @param array $datagridParameters
     * @param array $oldQueryParameters
     * @param array $expectedQueryParameters
     * @param bool $append
     */
    public function testBindParametersWorks(
        array $bindParameters,
        array $datagridParameters,
        array $oldQueryParameters,
        array $expectedQueryParameters,
        $append = true
    ) {
        $queryParameters = new ArrayCollection($oldQueryParameters);

        $this->datagrid->expects($this->once())
            ->method('getDatasource')
            ->will($this->returnValue($this->datasource));

        $this->datasource->expects($this->once())
            ->method('getQueryBuilder')
            ->will($this->returnValue($this->queryBuilder));

        $this->queryBuilder->expects($this->once())
            ->method('getParameters')
            ->will($this->returnValue($queryParameters));

        $this->datagrid->expects($this->once())
            ->method('getParameters')
            ->will($this->returnValue(new ParameterBag($datagridParameters)));

        $this->parameterBinder->bindParameters($this->datagrid, $bindParameters, $append);

        $this->assertEquals(
            $expectedQueryParameters,
            $queryParameters->toArray()
        );
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     * @return array
     */
    public function bindParametersDataProvider()
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
                    $this->createQueryParameter('entity_id', 1),
                    $this->createQueryParameter('entity_name', 'test'),
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
                    $this->createQueryParameter('entity_id', 1),
                    $this->createQueryParameter('entity_name', 'test'),
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
                        'type' => Type::STRING
                    ]
                ],
                'datagridParameters' => [
                    'entityId' => 1,
                    'entityName' => 'test',
                ],
                'oldQueryParameters' => [],
                'expectedQueryParameters' => [
                    $this->createQueryParameter('entity_id', 1),
                    $this->createQueryParameter('entity_name', 'test', Type::STRING),
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
                    $this->createQueryParameter('entity_name', 'test'),
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
                    $this->createQueryParameter('entity_name', 'test'),
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
                    $this->createQueryParameter('entity_id', 1),
                    $this->createQueryParameter('entity_name', 'test'),
                ]
            ],
            'append' => [
                'bindParameters' => ['entity_name'],
                'datagridParameters' => ['entity_name' => 'test'],
                'oldQueryParameters' => [$this->createQueryParameter('entity_id', 1)],
                'expectedQueryParameters' => [
                    $this->createQueryParameter('entity_id', 1),
                    $this->createQueryParameter('entity_name', 'test'),
                ]
            ],
            'replace' => [
                'bindParameters' => ['entity_id', 'entity_name'],
                'datagridParameters' => ['entity_id' => 1, 'entity_name' => 'test'],
                'oldQueryParameters' => [$this->createQueryParameter('entity_name', 'old')],
                'expectedQueryParameters' => [
                    1 => $this->createQueryParameter('entity_id', 1),
                    $this->createQueryParameter('entity_name', 'test'),
                ]
            ],
            'clear' => [
                'bindParameters' => ['entity_name'],
                'datagridParameters' => ['entity_name' => 'test'],
                'oldQueryParameters' => [$this->createQueryParameter('entity_id', 1)],
                'expectedQueryParameters' => [
                    $this->createQueryParameter('entity_name', 'test'),
                ],
                'append' => false,
            ],
        ];
    }

    public function testBindParametersFailsWithInvalidPath()
    {
        $datasource = $this->getMock('Oro\\Bundle\\DataGridBundle\\Datagrid\\DatagridInterface');

        $this->datagrid->expects($this->once())
            ->method('getDatasource')
            ->will($this->returnValue($datasource));

        $this->setExpectedException(
            'InvalidArgumentException',
            'Datagrid datasource has unexpected type "' . get_class($datasource) . '", ' .
            '"Oro\Bundle\DataGridBundle\Datasource\Orm\OrmDatasource" is expected.'
        );

        $this->parameterBinder->bindParameters($this->datagrid, ['foo']);
    }

    // @codingStandardsIgnoreStart
    /**
     * @expectedException \Oro\Bundle\DataGridBundle\Exception\InvalidArgumentException
     * @expectedExceptionMessage Cannot bind datasource parameter "bar", there is no datagrid parameter with path "foo.bar".
     */
    // @codingStandardsIgnoreEnd
    public function testBindParametersFailsWithInvalidDatasource()
    {
        $datagridParameters = ['foo' => new \stdClass];
        $queryParameters = new ArrayCollection();

        $this->datagrid->expects($this->once())
            ->method('getDatasource')
            ->will($this->returnValue($this->datasource));

        $this->datasource->expects($this->once())
            ->method('getQueryBuilder')
            ->will($this->returnValue($this->queryBuilder));

        $this->queryBuilder->expects($this->once())
            ->method('getParameters')
            ->will($this->returnValue($queryParameters));

        $this->datagrid->expects($this->once())
            ->method('getParameters')
            ->will($this->returnValue(new ParameterBag($datagridParameters)));

        $this->parameterBinder->bindParameters($this->datagrid, ['bar' => 'foo.bar']);
    }

    public function testBindParametersWorksWithEmptyParameters()
    {
        $this->datagrid->expects($this->never())->method($this->anything());

        $this->parameterBinder->bindParameters($this->datagrid, []);
    }

    public function testBindParametersFailsWithInvalidParameter()
    {
        $queryParameters = new ArrayCollection();

        $this->datagrid->expects($this->once())
            ->method('getDatasource')
            ->will($this->returnValue($this->datasource));

        $this->datasource->expects($this->once())
            ->method('getQueryBuilder')
            ->will($this->returnValue($this->queryBuilder));

        $this->queryBuilder->expects($this->once())
            ->method('getParameters')
            ->will($this->returnValue($queryParameters));

        $this->datagrid->expects($this->once())
            ->method('getParameters')
            ->will($this->returnValue(new ParameterBag()));

        $this->setExpectedException(
            'InvalidArgumentException',
            'Cannot bind parameter to data source, expected bind parameter format is a string or array with ' .
            'required "name" key, actual array keys are "foo"'
        );

        $this->parameterBinder->bindParameters($this->datagrid, [['foo' => 'bar']]);
    }

    /**
     * @param string $name
     * @param string $value
     * @param string  $type
     * @return Parameter
     */
    protected function createQueryParameter($name, $value, $type = null)
    {
        return new Parameter($name, $value, $type);
    }
}
