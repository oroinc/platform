<?php

namespace Oro\Bundle\CronBundle\Tests\Unit\Filter;

use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Query;

use Oro\Bundle\CronBundle\Filter\CommandWithArgsFilter;
use Oro\Bundle\FilterBundle\Datasource\Orm\OrmFilterDatasourceAdapter;
use Oro\Bundle\FilterBundle\Filter\FilterUtility;
use Oro\Bundle\FilterBundle\Form\Type\Filter\TextFilterType;

class CommandWithArgsFilterTest extends \PHPUnit_Framework_TestCase
{
    /** @var CommandWithArgsFilter */
    protected $filter;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->filter = new CommandWithArgsFilter(
            $this->getMock('Symfony\Component\Form\FormFactoryInterface'),
            new FilterUtility()
        );
        $this->filter->init('test', ['data_name' => 'j.command, j.args']);
    }

    /**
     * @dataProvider applyDataProvider
     */
    public function testApply($value, $comparisonType, $expectedDql, $expectedParams)
    {
        $paramCounter = 0;

        $em = $this->getMock('Doctrine\ORM\EntityManagerInterface');
        $qb = new QueryBuilder($em);
        $ds = $this->getMock(
            'Oro\Bundle\FilterBundle\Datasource\Orm\OrmFilterDatasourceAdapter',
            ['generateParameterName'],
            [$qb]
        );
        $ds->expects($this->any())
            ->method('generateParameterName')
            ->willReturnCallback(
                function () use (&$paramCounter) {
                    return sprintf('param%s', ++$paramCounter);
                }
            );

        $data = ['type' => $comparisonType, 'value' => $value];

        $em->expects($this->any())
            ->method('getExpressionBuilder')
            ->willReturn(new Query\Expr());

        $qb->select('j')->from('TestEntity', 'j');

        $this->filter->apply($ds, $data);
        $this->assertEquals($expectedDql, $qb->getDQL());
        $params = [];
        /** @var Query\Parameter $param */
        foreach ($qb->getParameters() as $param) {
            $params[$param->getName()] = $param->getValue();
        };
        $this->assertEquals($expectedParams, $params);
    }

    /**
     * @return array
     */
    public function applyDataProvider()
    {
        return [
//            [
//                'cmd --id=1 --id=2',
//                TextFilterType::TYPE_EQUAL,
//                'SELECT j FROM TestEntity j '
//                . 'WHERE j.command = :param1',
//                [
//                    'param1' => 'cmd --id=1 --id=2'
//                ]
//            ],
//            [
//                'cmd --id=1',
//                TextFilterType::TYPE_CONTAINS,
//                'SELECT j FROM TestEntity j '
//                . 'WHERE CONCAT(j.command, j.args) LIKE :param1 '
//                . 'AND CONCAT(j.command, j.args) LIKE :param2',
//                [
//                    'param1' => '%cmd%',
//                    'param2' => '%--id=1%'
//                ]
//            ],
//            [
//                'cmd --id=1 --id=2',
//                TextFilterType::TYPE_NOT_CONTAINS,
//                'SELECT j FROM TestEntity j '
//                . 'WHERE CONCAT(j.command, j.args) NOT LIKE :param1 '
//                . 'AND CONCAT(j.command, j.args) NOT LIKE :param2 '
//                . 'AND CONCAT(j.command, j.args) NOT LIKE :param3',
//                [
//                    'param1' => '%cmd%',
//                    'param2' => '%--id=1%',
//                    'param3' => '%--id=2%'
//                ]
//            ],
//            [
//                'cmd --prm1=Test --prm1="Test Param2"',
//                TextFilterType::TYPE_CONTAINS,
//                'SELECT j FROM TestEntity j '
//                . 'WHERE CONCAT(j.command, j.args) LIKE :param1 '
//                . 'AND CONCAT(j.command, j.args) LIKE :param2 '
//                . 'AND CONCAT(j.command, j.args) LIKE :param3',
//                [
//                    'param1' => '%cmd%',
//                    'param2' => '%--prm1=Test%',
//                    'param3' => '%--prm1=\\\\"Test Param2\\\\"%'
//                ]
//            ],
//            [
//                '--prm1="Test Param2"',
//                TextFilterType::TYPE_CONTAINS,
//                'SELECT j FROM TestEntity j '
//                . 'WHERE CONCAT(j.command, j.args) LIKE :param1',
//                [
//                    'param1' => '%--prm1=\\\\"Test Param2\\\\"%'
//                ]
//            ],
            [
                'cmd "Acme\\Class"',
                TextFilterType::TYPE_CONTAINS,
                'SELECT j FROM TestEntity j '
                . 'WHERE CONCAT(j.command, j.args) LIKE :param1 '
                . 'AND CONCAT(j.command, j.args) LIKE :param2',
                [
                    'param1' => '%cmd%',
                    'param2' => '%\\\\"Acme\\\\Class\\\\"%'
                ]
            ],
        ];
    }
}
