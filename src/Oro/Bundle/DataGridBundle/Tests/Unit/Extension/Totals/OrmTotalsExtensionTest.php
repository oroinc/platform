<?php

namespace Oro\Bundle\DataGridBundle\Tests\Unit\Extension\Totals;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\ORM\Mapping\Driver\AnnotationDriver;
use Doctrine\ORM\QueryBuilder;

use Oro\Bundle\DataGridBundle\Datagrid\Builder;
use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\Common\MetadataObject;
use Oro\Bundle\DataGridBundle\Datagrid\Common\ResultsObject;
use Oro\Bundle\DataGridBundle\Datasource\Orm\OrmDatasource;
use Oro\Bundle\DataGridBundle\Extension\Totals\OrmTotalsExtension;
use Oro\Bundle\DataGridBundle\Extension\Totals\Configuration;

use Oro\Bundle\TestFrameworkBundle\Test\Doctrine\ORM\Mocks\EntityManagerMock;
use Oro\Bundle\TestFrameworkBundle\Test\Doctrine\ORM\OrmTestCase;

class OrmTotalsExtensionTest extends OrmTestCase
{
    /**
     * @var OrmTotalsExtension
     */
    protected $extension;

    /**
     * @var DatagridConfiguration
     */
    protected $config;

    protected $translator;

    protected $numberFormatter;

    protected $dateTimeFormatter;

    public function setUp()
    {
        $this->translator = $this->getMockBuilder('Oro\Bundle\TranslationBundle\Translation\Translator')
            ->disableOriginalConstructor()
            ->getMock();
        $this->numberFormatter = $this->getMockBuilder('Oro\Bundle\LocaleBundle\Formatter\NumberFormatter')
            ->disableOriginalConstructor()
            ->getMock();
        $this->dateTimeFormatter = $this->getMockBuilder('Oro\Bundle\LocaleBundle\Formatter\DateTimeFormatter')
            ->disableOriginalConstructor()
            ->getMock();
        $this->config = $this->getTestConfig();

        $this->extension = new OrmTotalsExtension(
            $this->translator,
            $this->numberFormatter,
            $this->dateTimeFormatter
        );
    }

    public function testIsApplicable()
    {
        $this->assertTrue($this->extension->isApplicable($this->config));
        $this->config->offsetSetByPath(Builder::DATASOURCE_TYPE_PATH, 'non_orm');
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
                                'label' => 'Page Totals'
                            ]
                        ]
                    ]
                ]
            ]
        );
        $this->setExpectedException(
            '\Exception',
            'Total row "wrong_total_row" definition in "test_grid" datagrid config does not exist'
        );
        $this->extension->processConfigs($config);
    }

    public function testVisitMetadata()
    {
        $metadata = MetadataObject::create([]);
        $this->extension->visitMetadata($this->config, $metadata);
        $totalsData = $metadata->offsetGet('state');
        $this->assertEquals($this->config->offsetGetByPath(Configuration::TOTALS_PATH), $totalsData['totals']);
        $this->assertEquals('orodatagrid/js/totals-builder', $metadata->offsetGet('requireJSModules')[0]);
    }

    public function testGetPriority()
    {
        $this->assertEquals(-250, $this->extension->getPriority());
    }

    /**
     * @return DatagridConfiguration
     */
    protected function getTestConfig()
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
                        'columns' => [
                            'name' => ['label' => 'Page Totals']
                        ]
                    ],
                    'grand_total' =>[
                        'columns' => [
                            'id' => ['expr' => 'COUNT(a.id)'],
                            'name' => ['label' => 'Grand Totals'],
                            'wonCount' => ['expr' => 'SUM(a.won)']
                        ]
                    ]
                ]
            ]
        );
    }

    /**
     * @return ResultsObject
     */
    protected function getTestResult()
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
}
