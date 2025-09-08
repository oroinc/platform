<?php

namespace Oro\Bundle\DashboardBundle\Tests\Unit\EventListener;

use Oro\Bundle\DashboardBundle\Event\WidgetConfigurationLoadEvent;
use Oro\Bundle\DashboardBundle\EventListener\WidgetConfigurationLoadListener;
use Oro\Bundle\DataGridBundle\Datagrid\Builder;
use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\Common\MetadataObject;
use Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface;
use Oro\Bundle\DataGridBundle\Datagrid\Manager;
use Oro\Bundle\DataGridBundle\Datagrid\ParameterBag;
use Oro\Component\DependencyInjection\ServiceLink;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

class WidgetConfigurationLoadListenerTest extends TestCase
{
    private Builder&MockObject $datagridBuilder;
    private Manager&MockObject $datagridManager;
    private ServiceLink&MockObject $datagridManagerLink;
    private DatagridConfiguration&MockObject $datagridConfiguration;
    private DatagridInterface&MockObject $datagrid;
    private MetadataObject&MockObject $metadata;

    private WidgetConfigurationLoadListener $listener;

    protected function setUp(): void
    {
        $this->datagridBuilder = $this->createMock(Builder::class);
        $this->datagridManager = $this->createMock(Manager::class);
        $this->datagridConfiguration = $this->createMock(DatagridConfiguration::class);
        $this->datagrid = $this->createMock(DatagridInterface::class);
        $this->metadata = $this->createMock(MetadataObject::class);

        $this->datagridManagerLink = $this->createMock(ServiceLink::class);
        $this->datagridManagerLink->expects($this->any())
            ->method('getService')
            ->willReturn($this->datagridManager);

        $this->listener = new WidgetConfigurationLoadListener($this->datagridManagerLink, $this->datagridBuilder);
    }

    public function testOnWidgetConfigurationLoadUnsupportedRoute()
    {
        $configuration = [
            'route' => 'some_other_route'
        ];

        $this->datagridManager->expects($this->never())
            ->method('getConfigurationForGrid');

        $event = new WidgetConfigurationLoadEvent($configuration);
        $this->listener->onWidgetConfigurationLoad($event);

        $this->assertEquals($configuration, $event->getConfiguration());
    }

    public function testOnWidgetConfigurationLoadNoGridName()
    {
        $configuration = [
            'route' => 'oro_dashboard_grid',
            'route_parameters' => []
        ];

        $this->datagridManager->expects($this->never())
            ->method('getConfigurationForGrid');

        $event = new WidgetConfigurationLoadEvent($configuration);
        $this->listener->onWidgetConfigurationLoad($event);

        $this->assertEquals($configuration, $event->getConfiguration());
    }

    public function testOnWidgetConfigurationLoad()
    {
        $gridName = 'test-grid';
        $gridConfiguration = [
            'grid-param' => 'value'
        ];
        $configuration = [
            'route' => 'oro_dashboard_grid',
            'route_parameters' => [
                'gridName' => $gridName,
                'params' => $gridConfiguration,
            ],
            'configuration' => []
        ];

        $views = [
            ['label' => 'View 1', 'name' => 'view1'],
            ['label' => 'View 2', 'name' => 123],
        ];

        $this->datagridManager->expects($this->once())
            ->method('getConfigurationForGrid')
            ->with($gridName)
            ->willReturn($this->datagridConfiguration);

        $this->datagridBuilder->expects($this->once())
            ->method('build')
            ->with($this->datagridConfiguration, new ParameterBag($gridConfiguration))
            ->willReturn($this->datagrid);

        $this->datagrid->expects($this->once())
            ->method('getMetadata')
            ->willReturn($this->metadata);

        $this->metadata->expects($this->once())
            ->method('offsetGetByPath')
            ->with('[gridViews][views]', [])
            ->willReturn($views);

        $event = new WidgetConfigurationLoadEvent($configuration);
        $this->listener->onWidgetConfigurationLoad($event);

        $expectedConfiguration = [
            'route' => 'oro_dashboard_grid',
            'route_parameters' => [
                'gridName' => $gridName,
                'params' => [
                    'grid-param' => 'value',
                ],
            ],
            'fields' => [],
            'configuration' => [
                'gridView' => [
                    'type' => ChoiceType::class,
                    'options' => [
                        'label' => 'oro.dashboard.grid.fields.grid_view.label',
                        'choices' => [
                            'View 1' => 'view1',
                            'View 2' => 123,
                        ],
                    ],
                    'show_on_widget' => false,
                ]
            ]
        ];

        $this->assertEquals($expectedConfiguration, $event->getConfiguration());
    }
}
