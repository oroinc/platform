<?php

namespace Oro\Bundle\DashboardBundle\Tests\Unit\Form\Extension;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\DashboardBundle\DashboardType\DashboardTypeConfigProviderInterface;
use Oro\Bundle\DashboardBundle\DashboardType\WidgetsDashboardTypeConfigProvider;
use Oro\Bundle\DashboardBundle\Form\Extension\DashboardTypeExtension;
use Oro\Bundle\DashboardBundle\Form\Type\DashboardType;
use Oro\Bundle\DashboardBundle\Model\ConfigProvider;
use Oro\Bundle\DashboardBundle\Tests\Unit\Form\Extension\Stub\CloneableDashboardTypeConfigProviderStub;
use Oro\Bundle\EntityExtendBundle\Entity\EnumOption;
use Oro\Bundle\EntityExtendBundle\Entity\Repository\EnumOptionRepository;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;

class DashboardTypeExtensionTest extends TestCase
{
    private ManagerRegistry&MockObject $doctrine;
    private EnumOptionRepository&MockObject $enumRepository;
    private DashboardTypeExtension $extension;

    #[\Override]
    protected function setUp(): void
    {
        $this->doctrine = $this->createMock(ManagerRegistry::class);
        $this->enumRepository = $this->createMock(EnumOptionRepository::class);

        $this->doctrine->expects(self::any())
            ->method('getRepository')
            ->with(EnumOption::class)
            ->willReturn($this->enumRepository);
    }

    public function testGetExtendedTypes(): void
    {
        self::assertEquals([DashboardType::class], DashboardTypeExtension::getExtendedTypes());
    }

    public function testFinishViewWithoutDashboardTypeField(): void
    {
        $this->extension = new DashboardTypeExtension([], $this->doctrine);

        $view = new FormView();
        $form = $this->createMock(FormInterface::class);

        $this->enumRepository->expects(self::never())
            ->method('findBy');

        $this->extension->finishView($view, $form, []);

        self::assertArrayNotHasKey('cloneable_dashboard_types', $view->vars);
    }

    public function testFinishViewWithCloneableProvider(): void
    {
        $configProvider = $this->createMock(ConfigProvider::class);
        $cloneableProvider = new WidgetsDashboardTypeConfigProvider($configProvider);

        $this->extension = new DashboardTypeExtension([$cloneableProvider], $this->doctrine);

        $enumOption = new EnumOption('dashboard_type', 'Widgets', 'widgets');

        $this->enumRepository->expects(self::once())
            ->method('findBy')
            ->with(['enumCode' => 'dashboard_type'])
            ->willReturn([$enumOption]);

        $view = new FormView();
        $dashboardTypeView = new FormView($view);
        $view->children['dashboardType'] = $dashboardTypeView;
        $form = $this->createMock(FormInterface::class);

        $this->extension->finishView($view, $form, []);

        self::assertArrayHasKey('cloneable_dashboard_types', $dashboardTypeView->vars);
        self::assertEquals(['dashboard_type.widgets'], $dashboardTypeView->vars['cloneable_dashboard_types']);
    }

    public function testFinishViewWithNonCloneableProvider(): void
    {
        $nonCloneableProvider = $this->createMock(DashboardTypeConfigProviderInterface::class);
        $nonCloneableProvider->expects(self::once())
            ->method('isSupported')
            ->with('dashboard_type.seller')
            ->willReturn(true);

        $this->extension = new DashboardTypeExtension([$nonCloneableProvider], $this->doctrine);

        $enumOption = new EnumOption('dashboard_type', 'Seller', 'seller');

        $this->enumRepository->expects(self::once())
            ->method('findBy')
            ->with(['enumCode' => 'dashboard_type'])
            ->willReturn([$enumOption]);

        $view = new FormView();
        $dashboardTypeView = new FormView($view);
        $view->children['dashboardType'] = $dashboardTypeView;
        $form = $this->createMock(FormInterface::class);

        $this->extension->finishView($view, $form, []);

        self::assertArrayHasKey('cloneable_dashboard_types', $dashboardTypeView->vars);
        self::assertEquals([], $dashboardTypeView->vars['cloneable_dashboard_types']);
    }

    public function testFinishViewWithCloneableReturnsFalse(): void
    {
        $cloneableProvider = new CloneableDashboardTypeConfigProviderStub('dashboard_type.custom', false);

        $this->extension = new DashboardTypeExtension([$cloneableProvider], $this->doctrine);

        $enumOption = new EnumOption('dashboard_type', 'Custom', 'custom');

        $this->enumRepository->expects(self::once())
            ->method('findBy')
            ->with(['enumCode' => 'dashboard_type'])
            ->willReturn([$enumOption]);

        $view = new FormView();
        $dashboardTypeView = new FormView($view);
        $view->children['dashboardType'] = $dashboardTypeView;
        $form = $this->createMock(FormInterface::class);

        $this->extension->finishView($view, $form, []);

        self::assertArrayHasKey('cloneable_dashboard_types', $dashboardTypeView->vars);
        self::assertEquals([], $dashboardTypeView->vars['cloneable_dashboard_types']);
    }

    public function testFinishViewWithMultipleProviders(): void
    {
        $configProvider = $this->createMock(ConfigProvider::class);
        $cloneableProvider = new WidgetsDashboardTypeConfigProvider($configProvider);

        $nonCloneableProvider = $this->createMock(DashboardTypeConfigProviderInterface::class);
        $nonCloneableProvider->expects(self::once())
            ->method('isSupported')
            ->with('dashboard_type.seller')
            ->willReturn(true);

        $this->extension = new DashboardTypeExtension(
            [$cloneableProvider, $nonCloneableProvider],
            $this->doctrine
        );

        $enumOption1 = new EnumOption('dashboard_type', 'Widgets', 'widgets');
        $enumOption2 = new EnumOption('dashboard_type', 'Seller', 'seller');

        $this->enumRepository->expects(self::once())
            ->method('findBy')
            ->with(['enumCode' => 'dashboard_type'])
            ->willReturn([$enumOption1, $enumOption2]);

        $view = new FormView();
        $dashboardTypeView = new FormView($view);
        $view->children['dashboardType'] = $dashboardTypeView;
        $form = $this->createMock(FormInterface::class);

        $this->extension->finishView($view, $form, []);

        self::assertArrayHasKey('cloneable_dashboard_types', $dashboardTypeView->vars);
        self::assertEquals(['dashboard_type.widgets'], $dashboardTypeView->vars['cloneable_dashboard_types']);
    }
}
