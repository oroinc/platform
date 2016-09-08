<?php

namespace Oro\Bundle\LocaleBundle\Tests\Unit\Datagrid\Extension;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Persistence\Mapping\ClassMetadata;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Query\Expr;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\Common\ResultsObject;
use Oro\Bundle\DataGridBundle\Datasource\Orm\OrmDatasource;
use Oro\Bundle\DataGridBundle\Datasource\ResultRecord;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;

use Oro\Bundle\LocaleBundle\Datagrid\Extension\LocalizedValueExtension;
use Oro\Bundle\LocaleBundle\Datagrid\Formatter\Property\LocalizedValueProperty;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\Helper\LocalizationHelper;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class LocalizedValueExtensionTest extends \PHPUnit_Framework_TestCase
{
    /** @var DoctrineHelper|\PHPUnit_Framework_MockObject_MockObject */
    protected $doctrineHelper;

    /** @var LocalizationHelper|\PHPUnit_Framework_MockObject_MockObject */
    protected $localizationHelper;

    /** @var OrmDatasource|\PHPUnit_Framework_MockObject_MockObject */
    protected $datasource;

    /** @var ClassMetadata|\PHPUnit_Framework_MockObject_MockObject */
    protected $classMetadata;

    /** @var QueryBuilder|\PHPUnit_Framework_MockObject_MockObject */
    protected $queryBuilder;

    /** @var LocalizedValueExtension */
    protected $extension;

    /**
     * {@inheritdoc}
     */
    public function setUp()
    {
        $this->doctrineHelper = $this->getMockBuilder(DoctrineHelper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->localizationHelper = $this->getMockBuilder(LocalizationHelper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->datasource = $this->getMockBuilder(OrmDatasource::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->classMetadata = $this->getMock(ClassMetadata::class);

        $this->queryBuilder = $this->getMockBuilder(QueryBuilder::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->extension = new LocalizedValueExtension($this->doctrineHelper, $this->localizationHelper);
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

        $this->assertTrue($this->extension->isApplicable($config));
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

        $this->localizationHelper->expects($this->once())->method('getCurrentLocalization')->willReturn(null);

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

        $this->datasource->expects($this->never())->method($this->anything());

        $this->extension->visitDatasource($config, $this->datasource);
    }

    public function testVisitDatasourceWithoutRootAlias()
    {
        $config = DatagridConfiguration::create([]);

        $this->localizationHelper->expects($this->once())->method('getCurrentLocalization')->willReturn(null);

        $this->datasource->expects($this->never())->method($this->anything());

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

        $this->doctrineHelper->expects($this->once())
            ->method('getEntityMetadata')
            ->willReturn($this->classMetadata);

        $this->classMetadata->expects($this->once())
            ->method('getName')
            ->willReturn('Table1');

        $this->datasource->expects($this->once())
            ->method('getQueryBuilder')
            ->willReturn($this->queryBuilder);

        $this->queryBuilder->expects($this->at(0))
            ->method('addSelect')
            ->with('columnNames.string as columnName')
            ->willReturn($this->queryBuilder);

        $this->queryBuilder->expects($this->at(1))
            ->method('innerJoin')
            ->with('alias1.properties', 'columnNames', Expr\Join::WITH, 'columnNames.localization IS NULL')
            ->willReturn($this->queryBuilder);

        $this->queryBuilder->expects($this->at(2))
            ->method('getDQLPart')
            ->with('groupBy')
            ->willReturn(true);

        $this->queryBuilder->expects($this->at(3))
            ->method('addGroupBy')
            ->with('columnName');

        $this->extension->visitDatasource($config, $this->datasource);
    }

    public function testVisitResultWithoutCurrentLocalization()
    {
        $config = DatagridConfiguration::create([]);

        $this->localizationHelper->expects($this->once())->method('getCurrentLocalization')->willReturn(null);

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

        $this->doctrineHelper->expects($this->once())
            ->method('getEntityMetadata')
            ->willReturn($this->classMetadata);

        $this->classMetadata->expects($this->once())
            ->method('getName')
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
