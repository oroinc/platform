<?php

namespace Oro\Bundle\CronBundle\Tests\Unit\Filter;

use Doctrine\DBAL\Platforms\MySqlPlatform;
use Doctrine\DBAL\Platforms\PostgreSQL92Platform;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\CronBundle\Filter\CommandWithArgsFilter;
use Oro\Bundle\CronBundle\ORM\CommandArgsNormalizer;
use Oro\Bundle\CronBundle\ORM\CommandArgsTokenizer;
use Oro\Bundle\CronBundle\ORM\Pgsql92CommandArgsNormalizer;
use Oro\Bundle\FilterBundle\Filter\FilterUtility;
use Oro\Bundle\FilterBundle\Form\Type\Filter\TextFilterType;

class CommandWithArgsFilterTest extends \PHPUnit\Framework\TestCase
{
    /** @var CommandWithArgsFilter */
    protected $filter;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $tokenizer = new CommandArgsTokenizer();
        $tokenizer->addNormalizer(new CommandArgsNormalizer());
        $tokenizer->addNormalizer(new Pgsql92CommandArgsNormalizer());

        $this->filter = new CommandWithArgsFilter(
            $this->createMock('Symfony\Component\Form\FormFactoryInterface'),
            new FilterUtility(),
            $tokenizer
        );
        $this->filter->init('test', ['data_name' => 'j.command, j.args']);
    }

    /**
     * @dataProvider applyDataProvider
     */
    public function testApply($platform, $value, $comparisonType, $expectedDql, $expectedParams)
    {
        $paramCounter = 0;

        $em = $this->createMock('Doctrine\ORM\EntityManagerInterface');
        $qb = new QueryBuilder($em);
        $ds = $this->getMockBuilder('Oro\Bundle\FilterBundle\Datasource\Orm\OrmFilterDatasourceAdapter')
            ->setMethods(['generateParameterName', 'getDatabasePlatform'])
            ->setConstructorArgs([$qb])
            ->getMock();

        $ds->expects($this->any())
            ->method('generateParameterName')
            ->willReturnCallback(
                function () use (&$paramCounter) {
                    return sprintf('param%s', ++$paramCounter);
                }
            );
        $ds->expects($this->any())
            ->method('getDatabasePlatform')
            ->willReturn($platform);

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
            [
                new MySqlPlatform(),
                'cmd --id=1 --id=2',
                TextFilterType::TYPE_EQUAL,
                'SELECT j FROM TestEntity j '
                . 'WHERE j.command = :param1',
                [
                    'param1' => 'cmd --id=1 --id=2'
                ]
            ],
            [
                new MySqlPlatform(),
                'cmd --id=1',
                TextFilterType::TYPE_CONTAINS,
                'SELECT j FROM TestEntity j '
                . 'WHERE CONCAT(j.command, j.args) LIKE :param1 '
                . 'AND CONCAT(j.command, j.args) LIKE :param2',
                [
                    'param1' => '%cmd%',
                    'param2' => '%--id=1%'
                ]
            ],
            [
                new MySqlPlatform(),
                'cmd --id=1 --id=2',
                TextFilterType::TYPE_NOT_CONTAINS,
                'SELECT j FROM TestEntity j '
                . 'WHERE CONCAT(j.command, j.args) NOT LIKE :param1 '
                . 'AND CONCAT(j.command, j.args) NOT LIKE :param2 '
                . 'AND CONCAT(j.command, j.args) NOT LIKE :param3',
                [
                    'param1' => '%cmd%',
                    'param2' => '%--id=1%',
                    'param3' => '%--id=2%'
                ]
            ],
            [
                new MySqlPlatform(),
                'cmd --prm1=Test --prm1="Test Param2"',
                TextFilterType::TYPE_CONTAINS,
                'SELECT j FROM TestEntity j '
                . 'WHERE CONCAT(j.command, j.args) LIKE :param1 '
                . 'AND CONCAT(j.command, j.args) LIKE :param2 '
                . 'AND CONCAT(j.command, j.args) LIKE :param3',
                [
                    'param1' => '%cmd%',
                    'param2' => '%--prm1=Test%',
                    'param3' => '%--prm1=\\\\"Test Param2\\\\"%'
                ]
            ],
            [
                new MySqlPlatform(),
                '--prm1="Test Param\1"',
                TextFilterType::TYPE_CONTAINS,
                'SELECT j FROM TestEntity j '
                . 'WHERE CONCAT(j.command, j.args) LIKE :param1',
                [
                    'param1' => '%--prm1=\\\\"Test Param\\\\\\\\1\\\\"%'
                ]
            ],
            [
                new PostgreSQL92Platform(),
                '--prm1="Test Param\1"',
                TextFilterType::TYPE_CONTAINS,
                'SELECT j FROM TestEntity j '
                . 'WHERE CONCAT(j.command, j.args) LIKE :param1',
                [
                    'param1' => '%--prm1=\\\\"Test Param\\\\\\\\1\\\\"%'
                ]
            ],
            [
                new MySqlPlatform(),
                'cmd "Acme\\Class"',
                TextFilterType::TYPE_CONTAINS,
                'SELECT j FROM TestEntity j '
                . 'WHERE CONCAT(j.command, j.args) LIKE :param1 '
                . 'AND CONCAT(j.command, j.args) LIKE :param2',
                [
                    'param1' => '%cmd%',
                    'param2' => '%\\\\\\"Acme\\\\\\\\Class\\\\\\"%'
                ]
            ],
            [
                new PostgreSQL92Platform(),
                'cmd "Acme\\Class"',
                TextFilterType::TYPE_CONTAINS,
                'SELECT j FROM TestEntity j '
                . 'WHERE CONCAT(j.command, j.args) LIKE :param1 '
                . 'AND CONCAT(j.command, j.args) LIKE :param2',
                [
                    'param1' => '%cmd%',
                    'param2' => '%"Acme\\\\\\\\Class"%'
                ]
            ],
        ];
    }
}
