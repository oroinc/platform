<?php

namespace Oro\Bundle\DataGridBundle\Tests\Unit\Extension\Pager;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\ParameterBag;
use Oro\Bundle\DataGridBundle\Datasource\DatasourceInterface;
use Oro\Bundle\DataGridBundle\Extension\Mode\ModeExtension;
use Oro\Bundle\DataGridBundle\Extension\Pager\Orm\Pager;
use Oro\Bundle\DataGridBundle\Extension\Pager\OrmPagerExtension;
use Oro\Bundle\DataGridBundle\Extension\Pager\PagerInterface;

class OrmPagerExtensionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|Pager
     */
    protected $pager;

    /**
     * @var OrmPagerExtension
     */
    protected $extension;

    protected function setUp()
    {
        $this->pager = $this->getMockBuilder('Oro\Bundle\DataGridBundle\Extension\Pager\Orm\Pager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->extension = new OrmPagerExtension($this->pager);
    }

    /**
     * @param array $input
     * @param array $expected
     * @dataProvider setParametersDataProvider
     */
    public function testSetParameters(array $input, array $expected)
    {
        $this->extension->setParameters(new ParameterBag($input));
        $this->assertEquals($expected, $this->extension->getParameters()->all());
    }

    /**
     * @return array
     */
    public function setParametersDataProvider()
    {
        return array(
            'empty' => array(
                'input' => array(),
                'expected' => array(),
            ),
            'regular' => array(
                'input' => array(
                    PagerInterface::PAGER_ROOT_PARAM => array(
                        PagerInterface::PAGE_PARAM => 1,
                        PagerInterface::PER_PAGE_PARAM => 25,
                    )
                ),
                'expected' => array(
                    PagerInterface::PAGER_ROOT_PARAM => array(
                        PagerInterface::PAGE_PARAM => 1,
                        PagerInterface::PER_PAGE_PARAM => 25,
                    )
                )
            ),
            'minified' => array(
                'input' => array(
                    ParameterBag::MINIFIED_PARAMETERS => array(
                        PagerInterface::MINIFIED_PAGE_PARAM => 1,
                        PagerInterface::MINIFIED_PER_PAGE_PARAM => 25,
                    )
                ),
                'expected' => array(
                    ParameterBag::MINIFIED_PARAMETERS => array(
                        PagerInterface::MINIFIED_PAGE_PARAM => 1,
                        PagerInterface::MINIFIED_PER_PAGE_PARAM => 25,
                    ),
                    PagerInterface::PAGER_ROOT_PARAM => array(
                        PagerInterface::PAGE_PARAM => 1,
                        PagerInterface::PER_PAGE_PARAM => 25,
                    )
                )
            ),
        );
    }

    public function visitDatasourceNoRestrictionsDataProvider()
    {
        return [
            'regular grid' => [
                'config' => [],
                'page' => 1,
                'maxPerPage' => 10,
            ],
            'one page pagination' => [
                'config' => [
                    'options' => [
                        'toolbarOptions' => [
                            'pagination' => [
                                'onePage' => true
                            ]
                        ]
                    ]
                ],
                'page' => 0,
                'maxPerPage' => 0,
            ],
            'client mode' => [
                'config' => [
                    'options' => [
                        'mode' => ModeExtension::MODE_CLIENT,
                    ]
                ],
                'page' => 0,
                'maxPerPage' => 0,
            ],
        ];
    }

    /**
     * @param array $config
     * @param int $page
     * @param int $maxPerPage
     * @dataProvider visitDatasourceNoRestrictionsDataProvider
     */
    public function testVisitDatasourceNoPagerRestrictions(array $config, $page, $maxPerPage)
    {
        $this->pager->expects($this->once())
            ->method('setPage')
            ->with($page);
        $this->pager->expects($this->once())
            ->method('setMaxPerPage')
            ->with($maxPerPage);

        /** @var DatasourceInterface $dataSource */
        $dataSource = $this->getMock('Oro\Bundle\DataGridBundle\Datasource\DatasourceInterface');
        $configObject = DatagridConfiguration::create($config);

        $this->extension->setParameters(new ParameterBag());
        $this->extension->visitDatasource($configObject, $dataSource);
    }

    /**
     * @param null $count
     * @param bool $isSetCalculatedNbResults
     *
     * @dataProvider calculatedCountDataProvider
     */
    public function testVisitDatasourceWithCalculatedCount($count, $isSetCalculatedNbResults = false)
    {
        if ($isSetCalculatedNbResults) {
            $this->pager->expects($this->once())
                ->method('setCalculatedNbResults')
                ->with($count);
        } else {
            $this->pager->expects($this->never())
                ->method('setCalculatedNbResults')
                ->with($count);
        }

        /** @var DatasourceInterface $dataSource */
        $dataSource = $this->getMock('Oro\Bundle\DataGridBundle\Datasource\DatasourceInterface');
        $configObject = DatagridConfiguration::create([]);
        $parameters = [];
        if (null !== $count) {
            $parameters[PagerInterface::PAGER_ROOT_PARAM] = [PagerInterface::CALCULATED_COUNT => $count];
        }
        $this->extension->setParameters(new ParameterBag($parameters));
        $this->extension->visitDatasource($configObject, $dataSource);
    }

    public function calculatedCountDataProvider()
    {
        return [
            'valid value' => [150, true],
            'no value' => [null],
            'not valid value(negative)' => [-100],
            'not valid value(string)' => ['test'],
            'not valid value(false)' => [false],
            'not valid value(true)' => [true]
        ];
    }
}
