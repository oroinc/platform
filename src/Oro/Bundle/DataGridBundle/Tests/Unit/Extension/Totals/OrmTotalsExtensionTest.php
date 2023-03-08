<?php

namespace Oro\Bundle\DataGridBundle\Tests\Unit\Extension\Totals;

use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\Query\Expr\Andx;
use Doctrine\ORM\Query\Expr\Func;
use Doctrine\ORM\Query\Expr\GroupBy;
use Doctrine\ORM\Query\Expr\Select;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\Common\MetadataObject;
use Oro\Bundle\DataGridBundle\Datagrid\Common\ResultsObject;
use Oro\Bundle\DataGridBundle\Datagrid\ParameterBag;
use Oro\Bundle\DataGridBundle\Datasource\Orm\OrmDatasource;
use Oro\Bundle\DataGridBundle\Extension\Totals\Configuration;
use Oro\Bundle\DataGridBundle\Extension\Totals\OrmTotalsExtension;
use Oro\Bundle\LocaleBundle\Formatter\DateTimeFormatterInterface;
use Oro\Bundle\LocaleBundle\Formatter\NumberFormatter;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;
use Oro\Component\Testing\Unit\ORM\OrmTestCase;
use Symfony\Contracts\Translation\TranslatorInterface;

class OrmTotalsExtensionTest extends OrmTestCase
{
    /** @var DatagridConfiguration */
    private $config;

    /** @var NumberFormatter|\PHPUnit\Framework\MockObject\MockObject */
    private $numberFormatter;

    /** @var DateTimeFormatterInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $dateTimeFormatter;

    /** @var AclHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $aclHelper;

    /** @var OrmTotalsExtension */
    private $extension;

    protected function setUp(): void
    {
        $translator = $this->createMock(TranslatorInterface::class);
        $translator->expects($this->any())
            ->method('trans')
            ->willReturnArgument(0);

        $this->numberFormatter = $this->createMock(NumberFormatter::class);
        $this->dateTimeFormatter = $this->createMock(DateTimeFormatterInterface::class);
        $this->aclHelper = $this->createMock(AclHelper::class);

        $this->config = $this->getTestConfig();

        $this->extension = new OrmTotalsExtension(
            $translator,
            $this->numberFormatter,
            $this->dateTimeFormatter,
            $this->aclHelper
        );
        $this->extension->setParameters(new ParameterBag());
    }

    public function testIsApplicable()
    {
        $this->assertTrue($this->extension->isApplicable($this->config));
        $this->config->offsetSetByPath(DatagridConfiguration::DATASOURCE_TYPE_PATH, 'non_orm');
        $this->assertFalse($this->extension->isApplicable($this->config));
    }

    public function testProcessConfigs()
    {
        $this->extension->processConfigs($this->config);
        $resultConfig = $this->config->offsetGetByPath(Configuration::TOTALS_PATH);
        $this->assertTrue($resultConfig['total']['per_page']);
        $this->assertTrue($resultConfig['total']['hide_if_one_page']);
        $this->assertFalse($resultConfig['total']['columns']['name']['formatter']);
        $this->assertFalse($resultConfig['grand_total']['per_page']);
        $this->assertFalse($resultConfig['grand_total']['hide_if_one_page']);
        $this->assertEquals('SUM(a.won)', $resultConfig['grand_total']['columns']['wonCount']['expr']);
        $this->assertEquals(100, $resultConfig['grand_total']['columns']['wonCount']['divisor']);
        $this->assertTrue(isset($resultConfig['total']['columns']['wonCount']));
        $this->assertEquals('SUM(a.won)', $resultConfig['total']['columns']['wonCount']['expr']);
    }

    public function testWrongProcessConfigs()
    {
        $config = DatagridConfiguration::create(
            [
                'name' => 'test_grid',
                'source' => [
                    'type' => 'orm'
                ],
                'totals' => [
                    'total'=>[
                        'extends' => 'wrong_total_row',
                        'columns' => [
                            'name' => [
                                'label' => 'Page Total'
                            ]
                        ]
                    ]
                ]
            ]
        );
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage(
            'Total row "wrong_total_row" definition in "test_grid" datagrid config does not exist'
        );
        $this->extension->processConfigs($config);
    }

    public function testVisitMetadata()
    {
        $metadata = MetadataObject::create([]);
        $this->extension->visitMetadata($this->config, $metadata);
        $totalsData = $metadata->offsetGet('state');
        $initialTotalsData = $metadata->offsetGet('initialState');
        $this->assertEquals($totalsData, $initialTotalsData);
        $this->assertEquals($this->config->offsetGetByPath(Configuration::TOTALS_PATH), $totalsData['totals']);
        $this->assertEquals('orodatagrid/js/totals-builder', $metadata->offsetGet('jsmodules')[0]);
    }

    public function testGetPriority()
    {
        $this->assertEquals(-250, $this->extension->getPriority());
    }

    public function testVisitResult()
    {
        $config = $this->getTestConfig();
        $result = $this->getTestResult();

        $this->expectsQueryBuilderCalled($config);

        $this->extension->visitResult($config, $result);

        $this->assertEquals(
            [
                'totalRecords' => 14,
                'totals' => [
                    'total' => [],
                    'grand_total' => [
                        'columns' => [
                            'id' => [
                                'total' => 10
                            ],
                            'name' => [
                                'label' => 'Grand Total'
                            ],
                            'wonCount' => [
                                'total' => 0.55
                            ]
                        ]
                    ]
                ]
            ],
            $result->offsetGet('options')
        );
    }

    private function getTestConfig(): DatagridConfiguration
    {
        return DatagridConfiguration::create(
            [
                'name' => 'test_grid',
                'source' => [
                    'type' => 'orm'
                ],
                'totals' => [
                    'total'=>[
                        'extends' => 'grand_total',
                        'per_page' => true,
                        'hide_if_one_page' => true,
                        'disabled' => false,
                        'columns' => [
                            'name' => ['label' => 'Page Total']
                        ]
                    ],
                    'grand_total' => [
                        'per_page' => false,
                        'hide_if_one_page' => false,
                        'disabled' => false,
                        'columns' => [
                            'id' => ['expr' => 'COUNT(a.id)'],
                            'name' => ['label' => 'Grand Total'],
                            'wonCount' => ['expr' => 'SUM(a.won)', 'divisor' => 100]
                        ]
                    ]
                ]
            ]
        );
    }

    private function getTestResult(): ResultsObject
    {
        return ResultsObject::create([
            'data' => [
                ['id' => 1, 'name' => 'test1', 'wonCount' => 10],
                ['id' => 2, 'name' => 'test2', 'wonCount' => 4],
                ['id' => 3, 'name' => 'test3', 'wonCount' => 2],
                ['id' => 4, 'name' => 'test4', 'wonCount' => 6],
                ['id' => 5, 'name' => 'test5', 'wonCount' => 10],
                ['id' => 6, 'name' => 'test6', 'wonCount' => 9],
                ['id' => 7, 'name' => 'test7', 'wonCount' => 5],
                ['id' => 8, 'name' => 'test8', 'wonCount' => 4],
                ['id' => 9, 'name' => 'test9', 'wonCount' => 3],
                ['id' => 10, 'name' => 'test10', 'wonCount' => 2],
            ],
            'options' =>[
                'totalRecords' => 14
            ]
        ]);
    }

    private function expectsQueryBuilderCalled(DatagridConfiguration $config): void
    {
        $query = $this->getMockBuilder(AbstractQuery::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getScalarResult'])
            ->addMethods(['setFirstResult', 'setMaxResults'])
            ->getMockForAbstractClass();
        $query->expects($this->any())
            ->method('setFirstResult')
            ->willReturnSelf();
        $query->expects($this->any())
            ->method('setMaxResults')
            ->willReturnSelf();
        $query->expects($this->any())
            ->method('getScalarResult')
            ->willReturnOnConsecutiveCalls([], [], [['id' => 10, 'wonCount' => 55]]);

        $qb = $this->createMock(QueryBuilder::class);
        $qb->expects($this->any())
            ->method('getQuery')
            ->willReturn($query);
        $qb->expects($this->any())
            ->method('select')
            ->willReturnSelf();
        $qb->expects($this->any())
            ->method('expr')
            ->willReturn(new Expr());

        $having = new Andx(['id > 1', 'name is not null']);

        $qb->expects($this->any())
            ->method('getDQLPart')
            ->willReturnMap(
                [
                    ['select', [new Select(['id', 'name', 'wonCount'])]],
                    ['where', ''],
                    ['groupBy', [new GroupBy(['id'])]],
                    ['having', $having],
                ]
            );
        $qb->expects($this->atLeastOnce())
            ->method('andWhere')
            ->withConsecutive(
                [new Func('id IN', ':ids0')],
                [new Func('id IN', ':ids1')],
                [new Func('id IN', ':ids2')]
            )
            ->willReturnSelf();
        $qb->expects($this->atLeastOnce())
            ->method('setParameter')
            ->withConsecutive(
                ['ids0', [1, 2, 3, 4, 5, 6, 7, 8, 9, 10]],
                ['ids1', [1, 2, 3, 4, 5, 6, 7, 8, 9, 10]],
                ['ids2', [1, 2, 3, 4, 5, 6, 7, 8, 9, 10]]
            )
            ->willReturnSelf();
        $qb->expects($this->atLeastOnce())
            ->method('resetDQLParts')
            ->with(['groupBy', 'having'])
            ->willReturnSelf();
        $qb->expects($this->atLeastOnce())
            ->method('getParameters')
            ->willReturn([]);

        $this->aclHelper->expects($this->any())
            ->method('apply')
            ->willReturnArgument(0);

        $datasource = $this->createMock(OrmDatasource::class);
        $datasource->expects($this->any())
            ->method('getQueryBuilder')
            ->willReturn($qb);

        $this->extension->visitDatasource($config, $datasource);
    }
}
