<?php

namespace Oro\Bundle\DataGridBundle\Tests\Unit\Extension\Totals;

use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\Common\MetadataObject;
use Oro\Bundle\DataGridBundle\Datagrid\Common\ResultsObject;
use Oro\Bundle\DataGridBundle\Datagrid\ParameterBag;
use Oro\Bundle\DataGridBundle\Datasource\Orm\OrmDatasource;
use Oro\Bundle\DataGridBundle\Extension\Totals\Configuration;
use Oro\Bundle\DataGridBundle\Extension\Totals\OrmTotalsExtension;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\LocaleBundle\Formatter\DateTimeFormatterInterface;
use Oro\Bundle\LocaleBundle\Formatter\NumberFormatter;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;
use Oro\Bundle\TestFrameworkBundle\Entity\TestActivity;
use Oro\Component\DoctrineUtils\ORM\Walker\PostgreSqlOrderByNullsOutputResultModifier as OutputResultModifier;
use Oro\Component\TestUtils\ORM\OrmTestCase;
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

    /** @var DoctrineHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $doctrineHelper;

    /** @var OrmTotalsExtension */
    private $extension;

    protected function setUp(): void
    {
        $translator = self::createMock(TranslatorInterface::class);
        $translator->expects(self::any())
            ->method('trans')
            ->willReturnArgument(0);

        $this->numberFormatter = self::createMock(NumberFormatter::class);
        $this->dateTimeFormatter = self::createMock(DateTimeFormatterInterface::class);
        $this->aclHelper = self::createMock(AclHelper::class);
        $this->doctrineHelper = self::createMock(DoctrineHelper::class);

        $this->config = $this->getTestConfig();

        $this->extension = new OrmTotalsExtension(
            $translator,
            $this->numberFormatter,
            $this->dateTimeFormatter,
            $this->aclHelper
        );
        $this->extension->setDoctrineHelper($this->doctrineHelper);
        $this->extension->setParameters(new ParameterBag());
    }

    public function testIsApplicable()
    {
        self::assertTrue($this->extension->isApplicable($this->config));
        $this->config->offsetSetByPath(DatagridConfiguration::DATASOURCE_TYPE_PATH, 'non_orm');
        self::assertFalse($this->extension->isApplicable($this->config));
    }

    public function testProcessConfigs()
    {
        $this->extension->processConfigs($this->config);
        $resultConfig = $this->config->offsetGetByPath(Configuration::TOTALS_PATH);
        self::assertTrue($resultConfig['total']['per_page']);
        self::assertTrue($resultConfig['total']['hide_if_one_page']);
        self::assertFalse($resultConfig['total']['columns']['name']['formatter']);
        self::assertFalse($resultConfig['grand_total']['per_page']);
        self::assertFalse($resultConfig['grand_total']['hide_if_one_page']);
        self::assertEquals('SUM(a.won)', $resultConfig['grand_total']['columns']['wonCount']['expr']);
        self::assertEquals(100, $resultConfig['grand_total']['columns']['wonCount']['divisor']);
        self::assertTrue(isset($resultConfig['total']['columns']['wonCount']));
        self::assertEquals('SUM(a.won)', $resultConfig['total']['columns']['wonCount']['expr']);
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
        self::expectException(\Exception::class);
        self::expectExceptionMessage(
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
        self::assertEquals($totalsData, $initialTotalsData);
        self::assertEquals($this->config->offsetGetByPath(Configuration::TOTALS_PATH), $totalsData['totals']);
        self::assertEquals('orodatagrid/js/totals-builder', $metadata->offsetGet('jsmodules')[0]);
    }

    public function testGetPriority()
    {
        self::assertEquals(-PHP_INT_MAX, $this->extension->getPriority());
    }

    public function testVisitResult()
    {
        $config = $this->getTestConfig();
        $result = $this->getTestResult();

        $this->expectsQueryBuilderCalled($config);

        $this->extension->visitResult($config, $result);

        self::assertEquals(
            [
                'totalRecords' => 14,
                'totals' => [
                    'total' => [
                        'columns' => [
                            'name' => ['label' => 'Page Total']
                        ]
                    ],
                    'grand_total' => [
                        'columns' => [
                            'id' => [
                                'total' => 14
                            ],
                            'name' => [
                                'label' => 'Grand Total'
                            ],
                            'wonCount' => [
                                'total' => 0.14
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
        $this->doctrineHelper
            ->expects(self::any())
            ->method('getSingleEntityIdentifierFieldName')
            ->willReturn('id');

        $query = self::getMockBuilder(AbstractQuery::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['setHint', 'getArrayResult', 'getScalarResult'])
            ->addMethods(['setFirstResult', 'setMaxResults'])
            ->getMockForAbstractClass();

        $query
            ->expects(self::any())
            ->method('setFirstResult')
            ->willReturnSelf();
        $query
            ->expects(self::any())
            ->method('setMaxResults')
            ->willReturnSelf();
        $query
            ->expects(self::any())
            ->method('getArrayResult')
            ->willReturn([
                ['_identifier' => 1],
                ['_identifier' => 2],
                ['_identifier' => 3],
            ]);
        $query
            ->expects(self::any())
            ->method('getScalarResult')
            ->willReturn([
                ['id' => 14, 'wonCount' => 14]
            ]);
        $query
            ->expects(self::any())
            ->method('setHint')
            ->with(OutputResultModifier::HINT_DISABLE_ORDER_BY_MODIFICATION_NULLS, true);

        $qb = self::createMock(QueryBuilder::class);
        $qb
            ->expects(self::any())
            ->method('getRootAliases')
            ->willReturn(['root_alias']);
        $qb
            ->expects(self::any())
            ->method('getRootEntities')
            ->willReturn([TestActivity::class]);
        $qb
            ->expects(self::any())
            ->method('addSelect')
            ->with('GROUP_CONCAT(root_alias.id) as _identifier')
            ->willReturnSelf();
        $qb
            ->expects(self::any())
            ->method('getQuery')
            ->willReturn($query);
        $qb
            ->expects(self::any())
            ->method('setParameters')
            ->willReturn($query);
        $qb
            ->expects(self::any())
            ->method('expr')
            ->willReturn(new Expr());
        $qb
            ->expects(self::atLeastOnce())
            ->method('resetDQLParts')
            ->with(['groupBy', 'having', 'orderBy'])
            ->willReturnSelf();
        $qb
            ->expects(self::any())
            ->method('getParameters')
            ->willReturn([]);
        $qb
            ->expects(self::any())
            ->method('setMaxResults')
            ->willReturnSelf();
        $qb
            ->expects(self::any())
            ->method('setFirstResult')
            ->willReturnSelf();

        $this->aclHelper
            ->expects(self::any())
            ->method('apply')
            ->willReturnArgument(0);

        $datasource = self::createMock(OrmDatasource::class);
        $datasource->expects(self::any())
            ->method('getQueryBuilder')
            ->willReturn($qb);

        $this->extension->visitDatasource($config, $datasource);
    }
}
