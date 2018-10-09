<?php

namespace Oro\Bundle\DataGridBundle\Tests\Unit\Provider\State;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\ParameterBag;
use Oro\Bundle\DataGridBundle\Entity\Manager\GridViewManager;
use Oro\Bundle\DataGridBundle\Extension\GridViews\GridViewsExtension;
use Oro\Bundle\DataGridBundle\Extension\GridViews\ViewInterface;
use Oro\Bundle\DataGridBundle\Tools\DatagridParametersHelper;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;

abstract class AbstractStateProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var GridViewManager|\PHPUnit\Framework\MockObject\MockObject */
    protected $gridViewManager;

    /** @var TokenAccessorInterface|\PHPUnit\Framework\MockObject\MockObject */
    protected $tokenAccessor;

    /** @var DatagridParametersHelper|\PHPUnit\Framework\MockObject\MockObject */
    protected $datagridParametersHelper;

    /** @var DatagridConfiguration|\PHPUnit\Framework\MockObject\MockObject */
    protected $datagridConfiguration;

    /** @var ParameterBag|\PHPUnit\Framework\MockObject\MockObject */
    protected $datagridParameters;

    protected function setUp()
    {
        $this->gridViewManager = $this->createMock(GridViewManager::class);
        $this->tokenAccessor = $this->createMock(TokenAccessorInterface::class);
        $this->datagridParametersHelper = $this->createMock(DatagridParametersHelper::class);

        $this->datagridConfiguration = $this->createMock(DatagridConfiguration::class);
        $this->datagridParameters = $this->createMock(ParameterBag::class);
    }

    /**
     * @param string $gridName
     */
    protected function mockGridName(string $gridName): void
    {
        $this->datagridConfiguration
            ->expects(self::once())
            ->method('getName')
            ->willReturn($gridName);
    }

    /**
     * @param string|null $viewId
     */
    protected function mockCurrentGridViewId($viewId): void
    {
        $this->datagridParameters
            ->expects(self::exactly(2))
            ->method('get')
            ->willReturnMap([
                [GridViewsExtension::GRID_VIEW_ROOT_PARAM, [], []],
                [ParameterBag::ADDITIONAL_PARAMETERS, [], [GridViewsExtension::VIEWS_PARAM_KEY => $viewId]],
            ]);
    }

    /**
     * @param string $method
     * @param array $state
     *
     * @return ViewInterface
     */
    protected function mockGridView(string $method, array $state): ViewInterface
    {
        $gridView = $this->createMock(ViewInterface::class);
        $gridView
            ->expects(self::once())
            ->method($method)
            ->willReturn($state);

        return $gridView;
    }

    protected function assertNoCurrentGridView(): void
    {
        $this->mockCurrentGridViewId(null);

        $this->gridViewManager
            ->expects(self::never())
            ->method('getView');
    }

    protected function assertNoCurrentNoDefaultGridView(): void
    {
        $this->mockGridName($gridName = 'sample-datagrid');

        $this->assertNoCurrentGridView();

        $this->tokenAccessor
            ->expects(self::once())
            ->method('getUser')
            ->willReturn(null);

        $this->gridViewManager
            ->expects(self::never())
            ->method('getDefaultView');
    }

    protected function assertGridViewsDisabled(): void
    {
        $this->datagridParameters
            ->expects(self::once())
            ->method('get')
            ->with(GridViewsExtension::GRID_VIEW_ROOT_PARAM, [])
            ->willReturn([GridViewsExtension::DISABLED_PARAM => 1]);

        $this->datagridConfiguration
            ->expects(self::never())
            ->method('getName');
    }
}
