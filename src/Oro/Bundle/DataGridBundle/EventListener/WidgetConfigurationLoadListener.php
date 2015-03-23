<?php

namespace Oro\Bundle\DataGridBundle\EventListener;

use Oro\Bundle\DashboardBundle\Event\WidgetConfigurationLoadEvent;
use Oro\Bundle\DataGridBundle\Datagrid\Builder;
use Oro\Bundle\DataGridBundle\Datagrid\ParameterBag;
use Oro\Bundle\EntityConfigBundle\DependencyInjection\Utils\ServiceLink;

class WidgetConfigurationLoadListener
{
    /**
     * @var ServiceLink
     */
    protected $datagridManagerLink;

    /**
     * @var Builder
     */
    protected $datagridBuilder;

    /**
     * @param ServiceLink $datagridManagerLink
     * @param Builder     $datagridBuilder
     */
    public function __construct(ServiceLink $datagridManagerLink, Builder $datagridBuilder)
    {
        $this->datagridManagerLink = $datagridManagerLink;
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

        $gridConfiguration = $this->datagridManagerLink->getService()->getConfigurationForGrid($gridName);
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
