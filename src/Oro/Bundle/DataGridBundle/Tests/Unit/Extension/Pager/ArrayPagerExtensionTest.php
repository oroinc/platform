<?php

namespace Oro\Bundle\DataGridBundle\Tests\Unit\Extension\Pager;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\ParameterBag;
use Oro\Bundle\DataGridBundle\Datasource\ArrayDatasource\ArrayDatasource;
use Oro\Bundle\DataGridBundle\Datasource\Orm\OrmDatasource;
use Oro\Bundle\DataGridBundle\Extension\Mode\ModeExtension;
use Oro\Bundle\DataGridBundle\Extension\Pager\ArrayDatasource\ArrayPager;
use Oro\Bundle\DataGridBundle\Extension\Pager\ArrayPagerExtension;
use Oro\Bundle\DataGridBundle\Extension\Toolbar\ToolbarExtension;

class ArrayPagerExtensionTest extends \PHPUnit\Framework\TestCase
{
    /** @var ArrayPager|\PHPUnit\Framework\MockObject\MockObject */
    protected $pager;

    /** @var DatagridConfiguration|\PHPUnit\Framework\MockObject\MockObject $config **/
    protected $config;

    /** @var  ArrayDatasource */
    protected $arrayDatasource;

    /** @var ArrayPagerExtension */
    protected $arrayPagerExtension;

    protected function setUp()
    {
        $this->pager = $this->createMock(ArrayPager::class);
        $this->config = $this->createMock(DatagridConfiguration::class);
        $this->arrayPagerExtension = new ArrayPagerExtension($this->pager);
        $this->arrayPagerExtension->setParameters(new ParameterBag());
        $this->arrayDatasource = new ArrayDatasource();
        $this->arrayDatasource->setArraySource([]);
    }

    public function testGetPriority()
    {
        $this->assertEquals(-270, $this->arrayPagerExtension->getPriority());
    }

    public function testIsApplicableWithArrayDatasource()
    {
        $this->config->expects($this->once())->method('getDatasourceType')
            ->willReturn(ArrayDatasource::TYPE);

        $this->assertTrue($this->arrayPagerExtension->isApplicable($this->config));
    }

    public function testIsApplicableWithWrongDatasource()
    {
        $this->config->expects($this->once())->method('getDatasourceType')
            ->willReturn(OrmDatasource::TYPE);

        $this->assertFalse($this->arrayPagerExtension->isApplicable($this->config));
    }

    /**
     * @dataProvider pageDataProvider
     * @param bool $onePage
     * @param string $mode
     * @param int $perPageLimit
     * @param int $currentPage
     * @param int $maxPerPage
     */
    public function testVisitDatasource($onePage, $mode, $perPageLimit, $currentPage, $maxPerPage)
    {
        $this->prepareConfig($onePage, $mode, $perPageLimit, $perPageLimit);
        $this->preparePager($currentPage, $maxPerPage);
        $this->arrayPagerExtension->visitDatasource($this->config, $this->arrayDatasource);
    }

    /** @expectedException \Oro\Bundle\DataGridBundle\Exception\UnexpectedTypeException */
    public function testVisitDatasourceWithWrongSource()
    {
        $this->arrayPagerExtension->visitDatasource(
            $this->config,
            $this->createMock(OrmDatasource::class)
        );
    }

    public function pageDataProvider()
    {
        return [
            [
                'onePage' => false,
                'mode' => ModeExtension::MODE_CLIENT,
                'perPageLimit' => 25,
                'expectedCurrentPage' => 0,
                'expectedMaxPerPage' => 0,
            ],
            [
                'onePage' => true,
                'mode' => ModeExtension::MODE_SERVER,
                'perPageLimit' => 0,
                'expectedCurrentPage' => 0,
                'expectedMaxPerPage' => 0,
            ],
            [
                'onePage' => true,
                'mode' => ModeExtension::MODE_SERVER,
                'perPageLimit' => 25,
                'expectedCurrentPage' => 0,
                'expectedMaxPerPage' => 25,
            ],
            [
                'onePage' => false,
                'mode' => ModeExtension::MODE_SERVER,
                'perPageLimit' => 25,
                'expectedCurrentPage' => 1,
                'expectedMaxPerPage' => 25,
            ],
        ];
    }

    /**
     * @param bool $onePage
     * @param string $mode
     * @param int $perPageLimit
     * @param int $defaultPerPage
     */
    protected function prepareConfig($onePage, $mode, $perPageLimit, $defaultPerPage)
    {
        $this->config->expects($this->at(0))->method('offsetGetByPath')
            ->with(ToolbarExtension::PAGER_ONE_PAGE_OPTION_PATH, false)->willReturn($onePage);

        $this->config->expects($this->at(1))->method('offsetGetByPath')
            ->with(ModeExtension::MODE_OPTION_PATH)->willReturn($mode);

        $this->config->expects($this->at(2))->method('offsetGetByPath')
            ->with(ToolbarExtension::PAGER_DEFAULT_PER_PAGE_OPTION_PATH)->willReturn($perPageLimit);

        $this->config->expects($this->at(3))->method('offsetGetByPath')
            ->with(ToolbarExtension::PAGER_DEFAULT_PER_PAGE_OPTION_PATH, 10)->willReturn($defaultPerPage);
    }

    protected function preparePager($currentPage, $maxPerPage)
    {
        $this->pager->expects($this->once())->method('setPage')->with($currentPage);
        $this->pager->expects($this->once())->method('setMaxPerPage')->with($maxPerPage);
        $this->pager->expects($this->once())->method('apply')->with($this->arrayDatasource);
    }
}
