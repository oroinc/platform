<?php

namespace Oro\Bundle\DataGridBundle\EventListener;

use Oro\Bundle\DashboardBundle\Event\WidgetConfigurationLoadEvent;
use Oro\Bundle\DataGridBundle\Datagrid\Builder;
use Oro\Bundle\DataGridBundle\Datagrid\Manager;
use Oro\Bundle\DataGridBundle\Datagrid\ParameterBag;

class WidgetConfigurationLoadListener
{
    /**
     * @var Manager
     */
    protected $datagridManager;

    /**
     * @var Builder
     */
    protected $datagridBuilder;

    /**
     * @param Manager $datagridManager
     * @param Builder $datagridBuilder
     */
    public function __construct(Manager $datagridManager, Builder $datagridBuilder)
    {
        $this->datagridManager = $datagridManager;
        $this->datagridBuilder = $datagridBuilder;
    }

    /**
     * @param WidgetConfigurationLoadEvent $event
     */
    public function onWidgetConfigurationLoad(WidgetConfigurationLoadEvent $event)
    {
        $configuration = $event->getConfiguration();
        if (
            !isset($configuration['route']) ||
            !isset($configuration['route_parameters']) ||
            !isset($configuration['route_parameters']['gridName']) ||
            $configuration['route'] !== 'oro_datagrid_dashboard_grid'
        ) {
            return;
        }
        $gridName = $configuration['route_parameters']['gridName'];

        $gridConfiguration = $this->datagridManager->getConfigurationForGrid($gridName);
        $datagrid = $this->datagridBuilder->build($gridConfiguration, new ParameterBag());
        $metadata = $datagrid->getMetadata();

        $choices = $metadata->offsetGetByPath('[gridViews][choices]', []);
        $viewChoices = [];
        foreach ($choices as $choice) {
            $viewChoices[$choice['value']] = $choice['label'];
        }
        if (!isset($configuration['fields'])) {
            $configuration['fields'] = [];
        }

        $configuration['fields'] = array_merge($configuration['fields'], [
            'gridView' => [
                'type' => 'choice',
                'options' => [
                    'choices' => $viewChoices,
                ],
            ]
        ]);

        $event->setConfiguration($configuration);
    }
}
