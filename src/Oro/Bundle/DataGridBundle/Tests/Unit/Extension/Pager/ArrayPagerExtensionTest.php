<?php

namespace Oro\Bundle\DataGridBundle\Tests\Unit\Extension\Pager;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\ParameterBag;
use Oro\Bundle\DataGridBundle\Datasource\ArrayDatasource\ArrayDatasource;
use Oro\Bundle\DataGridBundle\Datasource\Orm\OrmDatasource;
use Oro\Bundle\DataGridBundle\Exception\UnexpectedTypeException;
use Oro\Bundle\DataGridBundle\Extension\Mode\ModeExtension;
use Oro\Bundle\DataGridBundle\Extension\Pager\ArrayDatasource\ArrayPager;
use Oro\Bundle\DataGridBundle\Extension\Pager\ArrayPagerExtension;
use Oro\Bundle\DataGridBundle\Extension\Toolbar\ToolbarExtension;

class ArrayPagerExtensionTest extends \PHPUnit\Framework\TestCase
{
    /** @var ArrayPager|\PHPUnit\Framework\MockObject\MockObject */
    private $pager;

    /** @var DatagridConfiguration|\PHPUnit\Framework\MockObject\MockObject */
    private $config;

    /** @var ArrayDatasource */
    private $arrayDatasource;

    /** @var ArrayPagerExtension */
    private $arrayPagerExtension;

    protected function setUp(): void
    {
        $this->pager = $this->createMock(ArrayPager::class);
        $this->config = $this->createMock(DatagridConfiguration::class);
        $this->arrayDatasource = new ArrayDatasource();
        $this->arrayDatasource->setArraySource([]);

        $this->arrayPagerExtension = new ArrayPagerExtension($this->pager);
        $this->arrayPagerExtension->setParameters(new ParameterBag());
    }

    public function testGetPriority()
    {
        $this->assertEquals(-270, $this->arrayPagerExtension->getPriority());
    }

    public function testIsApplicableWithArrayDatasource()
    {
        $this->config->expects($this->once())
            ->method('getDatasourceType')
            ->willReturn(ArrayDatasource::TYPE);

        $this->assertTrue($this->arrayPagerExtension->isApplicable($this->config));
    }

    public function testIsApplicableWithWrongDatasource()
    {
        $this->config->expects($this->once())
            ->method('getDatasourceType')
            ->willReturn(OrmDatasource::TYPE);

        $this->assertFalse($this->arrayPagerExtension->isApplicable($this->config));
    }

    /**
     * @dataProvider pageDataProvider
     */
    public function testVisitDatasource(
        bool $onePage,
        string $mode,
        int $perPageLimit,
        int $currentPage,
        int $maxPerPage
    ) {
        $this->config->expects($this->exactly(4))
            ->method('offsetGetByPath')
            ->willReturnMap([
                [ToolbarExtension::PAGER_ONE_PAGE_OPTION_PATH, false, $onePage],
                [ModeExtension::MODE_OPTION_PATH, null, $mode],
                [ToolbarExtension::PAGER_DEFAULT_PER_PAGE_OPTION_PATH, null, $perPageLimit],
                [ToolbarExtension::PAGER_DEFAULT_PER_PAGE_OPTION_PATH, 10, $perPageLimit]
            ]);

        $this->pager->expects($this->once())
            ->method('setPage')
            ->with($currentPage);
        $this->pager->expects($this->once())
            ->method('setMaxPerPage')
            ->with($maxPerPage);
        $this->pager->expects($this->once())
            ->method('apply')
            ->with($this->arrayDatasource);

        $this->arrayPagerExtension->visitDatasource($this->config, $this->arrayDatasource);
    }

    public function testVisitDatasourceWithWrongSource()
    {
        $this->expectException(UnexpectedTypeException::class);

        $this->arrayPagerExtension->visitDatasource(
            $this->config,
            $this->createMock(OrmDatasource::class)
        );
    }

    public function pageDataProvider(): array
    {
        return [
            [
                'onePage'             => false,
                'mode'                => ModeExtension::MODE_CLIENT,
                'perPageLimit'        => 25,
                'expectedCurrentPage' => 0,
                'expectedMaxPerPage'  => 0,
            ],
            [
                'onePage'             => true,
                'mode'                => ModeExtension::MODE_SERVER,
                'perPageLimit'        => 0,
                'expectedCurrentPage' => 0,
                'expectedMaxPerPage'  => 0,
            ],
            [
                'onePage'             => true,
                'mode'                => ModeExtension::MODE_SERVER,
                'perPageLimit'        => 25,
                'expectedCurrentPage' => 0,
                'expectedMaxPerPage'  => 25,
            ],
            [
                'onePage'             => false,
                'mode'                => ModeExtension::MODE_SERVER,
                'perPageLimit'        => 25,
                'expectedCurrentPage' => 1,
                'expectedMaxPerPage'  => 25,
            ],
        ];
    }
}
