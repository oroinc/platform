<?php

namespace Oro\Bundle\DataGridBundle\Tests\Unit\Provider\State;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\ParameterBag;
use Oro\Bundle\DataGridBundle\Entity\Manager\GridViewManager;
use Oro\Bundle\DataGridBundle\Extension\GridViews\GridViewsExtension;
use Oro\Bundle\DataGridBundle\Extension\GridViews\ViewInterface;
use Oro\Bundle\DataGridBundle\Tools\DatagridParametersHelper;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

abstract class AbstractStateProviderTest extends TestCase
{
    protected GridViewManager&MockObject $gridViewManager;
    protected TokenAccessorInterface&MockObject $tokenAccessor;
    protected DatagridParametersHelper&MockObject $datagridParametersHelper;
    protected DatagridConfiguration&MockObject $datagridConfiguration;
    protected ParameterBag&MockObject $datagridParameters;

    #[\Override]
    protected function setUp(): void
    {
        $this->gridViewManager = $this->createMock(GridViewManager::class);
        $this->tokenAccessor = $this->createMock(TokenAccessorInterface::class);
        $this->datagridParametersHelper = $this->createMock(DatagridParametersHelper::class);
        $this->datagridConfiguration = $this->createMock(DatagridConfiguration::class);
        $this->datagridParameters = $this->createMock(ParameterBag::class);
    }

    protected function mockGridName(string $gridName): void
    {
        $this->datagridConfiguration->expects(self::once())
            ->method('getName')
            ->willReturn($gridName);
    }

    protected function mockCurrentGridViewId(?string $viewId): void
    {
        $this->datagridParameters->expects(self::exactly(2))
            ->method('get')
            ->willReturnMap([
                [GridViewsExtension::GRID_VIEW_ROOT_PARAM, [], []],
                [ParameterBag::ADDITIONAL_PARAMETERS, [], [GridViewsExtension::VIEWS_PARAM_KEY => $viewId]],
            ]);
    }

    protected function mockGridView(string $method, array $state): ViewInterface
    {
        $gridView = $this->createMock(ViewInterface::class);
        $gridView->expects(self::once())
            ->method($method)
            ->willReturn($state);

        return $gridView;
    }

    protected function assertNoCurrentGridView(): void
    {
        $this->mockCurrentGridViewId(null);

        $this->gridViewManager->expects(self::never())
            ->method('getView');
    }

    protected function assertNoCurrentNoDefaultGridView(): void
    {
        $this->mockGridName('sample-datagrid');

        $this->assertNoCurrentGridView();

        $this->tokenAccessor->expects(self::once())
            ->method('getUser')
            ->willReturn(null);

        $this->gridViewManager->expects(self::never())
            ->method('getDefaultView');
    }

    protected function assertGridViewsDisabled(): void
    {
        $this->datagridParameters->expects(self::once())
            ->method('get')
            ->with(GridViewsExtension::GRID_VIEW_ROOT_PARAM, [])
            ->willReturn([GridViewsExtension::DISABLED_PARAM => 1]);

        $this->datagridConfiguration->expects(self::never())
            ->method('getName');
    }
}
