<?php

namespace Oro\Bundle\LocaleBundle\Tests\Unit\Datagrid\Extension;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Inflector\Rules\English\InflectorFactory;
use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\Common\ResultsObject;
use Oro\Bundle\DataGridBundle\Datagrid\ParameterBag;
use Oro\Bundle\DataGridBundle\Datasource\Orm\OrmDatasource;
use Oro\Bundle\DataGridBundle\Datasource\ResultRecord;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityBundle\ORM\EntityClassResolver;
use Oro\Bundle\LocaleBundle\Datagrid\Extension\LocalizedValueExtension;
use Oro\Bundle\LocaleBundle\Datagrid\Formatter\Property\LocalizedValueProperty;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\Helper\LocalizationHelper;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class LocalizedValueExtensionTest extends \PHPUnit\Framework\TestCase
{
    /** @var DoctrineHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $doctrineHelper;

    /** @var EntityClassResolver|\PHPUnit\Framework\MockObject\MockObject */
    private $entityClassResolver;

    /** @var LocalizationHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $localizationHelper;

    /** @var OrmDatasource|\PHPUnit\Framework\MockObject\MockObject */
    private $datasource;

    /** @var QueryBuilder|\PHPUnit\Framework\MockObject\MockObject */
    private $queryBuilder;

    /** @var LocalizedValueExtension */
    private $extension;

    protected function setUp(): void
    {
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);
        $this->entityClassResolver = $this->createMock(EntityClassResolver::class);
        $this->localizationHelper = $this->createMock(LocalizationHelper::class);
        $this->datasource = $this->createMock(OrmDatasource::class);
        $this->queryBuilder = $this->createMock(QueryBuilder::class);

        $this->extension = new LocalizedValueExtension(
            $this->doctrineHelper,
            $this->entityClassResolver,
            $this->localizationHelper,
            (new InflectorFactory())->build()
        );
        $this->extension->setParameters(new ParameterBag());
    }

    public function testApplicable()
    {
        $config = DatagridConfiguration::create([
            'properties' => [
                'property1' => [
                    LocalizedValueProperty::TYPE_KEY => LocalizedValueProperty::NAME,
                ],
            ],
        ]);

        // test with orm datasource type
        $config = $config->offsetSetByPath(DatagridConfiguration::DATASOURCE_TYPE_PATH, OrmDatasource::TYPE);
        $this->assertTrue($this->extension->isApplicable($config));

        // test with invalid datasource type
        $config = $config->offsetSetByPath(
            DatagridConfiguration::DATASOURCE_TYPE_PATH,
            'anything_but_not_orm'
        );
        $this->assertFalse($this->extension->isApplicable($config));
    }

    public function testNotApplicable()
    {
        $config = DatagridConfiguration::create([
            'properties' => [
                'property1' => [],
            ],
        ]);

        $this->assertFalse($this->extension->isApplicable($config));
    }

    public function testProcessConfigsWithoutCurrentLocalization()
    {
        $config = DatagridConfiguration::create([]);
        $clonedConfig = clone $config;

        $this->localizationHelper->expects($this->once())
            ->method('getCurrentLocalization')
            ->willReturn(null);

        $this->extension->processConfigs($clonedConfig);

        $this->assertEquals($config, $clonedConfig);
    }

    public function testProcessConfigs()
    {
        $config = DatagridConfiguration::create([
            'properties' => [
                'column1' => [
                    LocalizedValueProperty::TYPE_KEY => LocalizedValueProperty::NAME,
                ],
            ],
            'sorters' => ['columns' => ['column1' => []]],
            'filters' => ['columns' => ['column1' => []]],
        ]);

        $expectedConfig = DatagridConfiguration::create([
            'properties' => [
                'column1' => [
                    LocalizedValueProperty::TYPE_KEY => LocalizedValueProperty::NAME,
                ],
            ],
            'sorters' => ['columns' => []],
            'filters' => ['columns' => []],
        ]);

        $this->localizationHelper->expects($this->once())
            ->method('getCurrentLocalization')
            ->willReturn(new Localization());

        $this->extension->processConfigs($config);

        $this->assertEquals($expectedConfig->toArray(), $config->toArray());
    }

    public function testVisitDatasourceWithCurrentLocalization()
    {
        $config = DatagridConfiguration::create([]);

        $this->localizationHelper->expects($this->once())
            ->method('getCurrentLocalization')
            ->willReturn(new Localization());

        $this->datasource->expects($this->never())
            ->method($this->anything());

        $this->extension->visitDatasource($config, $this->datasource);
    }

    public function testVisitDatasourceWithoutRootAlias()
    {
        $config = DatagridConfiguration::create([]);

        $this->localizationHelper->expects($this->once())
            ->method('getCurrentLocalization')
            ->willReturn(null);

        $this->datasource->expects($this->never())
            ->method($this->anything());

        $this->extension->visitDatasource($config, $this->datasource);
    }

    public function testVisitDatasource()
    {
        $config = DatagridConfiguration::create([
            'properties' => [
                'columnName' => [
                    LocalizedValueProperty::TYPE_KEY => LocalizedValueProperty::NAME,
                    LocalizedValueProperty::DATA_NAME_KEY => 'property',
                ],
            ],
            'source' => [
                'query' => [
                    'from' => [
                        [
                            'table' => 'Table1',
                            'alias' => 'alias1',
                        ],
                    ],
                ]
            ],
        ]);

        $this->localizationHelper->expects($this->once())
            ->method('getCurrentLocalization')
            ->willReturn(null);

        $this->datasource->expects($this->once())
            ->method('getQueryBuilder')
            ->willReturn($this->queryBuilder);

        $this->queryBuilder->expects($this->once())
            ->method('addSelect')
            ->with('columnNames.string as columnName')
            ->willReturnSelf();

        $this->queryBuilder->expects($this->once())
            ->method('innerJoin')
            ->with('alias1.properties', 'columnNames', Expr\Join::WITH, 'columnNames.localization IS NULL')
            ->willReturnSelf();

        $this->queryBuilder->expects($this->once())
            ->method('getDQLPart')
            ->with('groupBy')
            ->willReturnSelf();

        $this->queryBuilder->expects($this->once())
            ->method('addGroupBy')
            ->with('columnName')
            ->willReturnSelf();

        $expr = new Expr();
        $this->queryBuilder->expects($this->any())
            ->method('expr')
            ->willReturn($expr);

        $this->extension->visitDatasource($config, $this->datasource);
    }

    public function testVisitDatasourceAllowingEmpty()
    {
        $config = DatagridConfiguration::create([
            'properties' => [
                'columnName' => [
                    LocalizedValueProperty::TYPE_KEY => LocalizedValueProperty::NAME,
                    LocalizedValueProperty::DATA_NAME_KEY => 'property',
                    LocalizedValueProperty::ALLOW_EMPTY => 'true',
                ],
            ],
            'source' => [
                'query' => [
                    'from' => [
                        [
                            'table' => 'Table1',
                            'alias' => 'alias1',
                        ],
                    ],
                ]
            ],
        ]);

        $this->localizationHelper->expects($this->once())
            ->method('getCurrentLocalization')
            ->willReturn(null);

        $this->datasource->expects($this->once())
            ->method('getQueryBuilder')
            ->willReturn($this->queryBuilder);

        $this->queryBuilder->expects($this->once())
            ->method('addSelect')
            ->with('columnNames.string as columnName')
            ->willReturnSelf();

        $this->queryBuilder->expects($this->once())
            ->method('leftJoin')
            ->with('alias1.properties', 'columnNames', Expr\Join::WITH, 'columnNames.localization IS NULL')
            ->willReturnSelf();

        $this->queryBuilder->expects($this->once())
            ->method('andWhere')
            ->willReturnSelf();

        $this->queryBuilder->expects($this->once())
            ->method('getDQLPart')
            ->with('groupBy')
            ->willReturnSelf();

        $this->queryBuilder->expects($this->once())
            ->method('addGroupBy')
            ->with('columnName')
            ->willReturnSelf();

        $expr = new Expr();
        $this->queryBuilder->expects($this->any())
            ->method('expr')
            ->willReturn($expr);

        $this->extension->visitDatasource($config, $this->datasource);
    }

    public function testVisitResultWithoutCurrentLocalization()
    {
        $config = DatagridConfiguration::create([]);

        $this->localizationHelper->expects($this->once())
            ->method('getCurrentLocalization')
            ->willReturn(null);

        $result = ResultsObject::create([]);

        $this->extension->visitResult($config, $result);
    }

    public function testVisitResultWithoutRootAlias()
    {
        $config = DatagridConfiguration::create([]);

        $this->localizationHelper->expects($this->once())
            ->method('getCurrentLocalization')
            ->willReturn(new Localization());

        $result = ResultsObject::create([]);

        $this->extension->visitResult($config, $result);
    }

    public function testVisitResult()
    {
        $config = DatagridConfiguration::create([
            'properties' => [
                'column1Name' => [
                    LocalizedValueProperty::TYPE_KEY => LocalizedValueProperty::NAME,
                    LocalizedValueProperty::DATA_NAME_KEY => 'property1',
                ],
                'column2Name' => [
                    LocalizedValueProperty::TYPE_KEY => LocalizedValueProperty::NAME,
                    LocalizedValueProperty::DATA_NAME_KEY => 'property2',
                ],
            ],
            'source' => [
                'query' => [
                    'from' => [
                        [
                            'table' => 'Table1',
                            'alias' => 'alias1',
                        ],
                    ],
                ]
            ],
        ]);

        $this->localizationHelper->expects($this->once())
            ->method('getCurrentLocalization')
            ->willReturn(new Localization());

        $this->entityClassResolver->expects($this->once())
            ->method('getEntityClass')
            ->with('Table1')
            ->willReturn('Entity1');

        $this->doctrineHelper->expects($this->once())
            ->method('getSingleEntityIdentifierFieldName')
            ->with('Entity1', false)
            ->willReturn('primaryKey');

        $result = ResultsObject::create([
            'data' => [
                new ResultRecord([
                    'primaryKey' => 1
                ]),
            ]
        ]);

        $this->doctrineHelper->expects($this->once())
            ->method('getEntity')
            ->with('Entity1', 1)
            ->willReturn((object)[
                'property1' => new ArrayCollection(),
                'property2' => 'normalValue',
            ]);

        $this->localizationHelper->expects($this->once())
            ->method('getLocalizedValue')
            ->with(new ArrayCollection())
            ->willReturn('localizedValue');

        $this->extension->visitResult($config, $result);

        $record = new ResultRecord(['primaryKey' => 1]);
        $record->addData([
            'column1Name' => 'localizedValue',
            'column2Name' => 'normalValue',
        ]);

        $this->assertEquals([$record], $result->getData());
    }

    public function testGetPriority()
    {
        $this->assertEquals(200, $this->extension->getPriority());
    }
}
