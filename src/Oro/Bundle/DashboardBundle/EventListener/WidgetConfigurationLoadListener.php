<?php

namespace Oro\Bundle\DashboardBundle\EventListener;

use Oro\Bundle\DashboardBundle\Event\WidgetConfigurationLoadEvent;
use Oro\Bundle\DataGridBundle\Datagrid\Builder;
use Oro\Bundle\DataGridBundle\Datagrid\ParameterBag;
use Oro\Component\DependencyInjection\ServiceLink;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

/**
 * Sets datagrid grid views options for the datagrid widget
 */
class WidgetConfigurationLoadListener
{
    /** @var ServiceLink */
    protected $datagridManagerLink;

    /** @var Builder */
    protected $datagridBuilder;

    public function __construct(ServiceLink $datagridManagerLink, Builder $datagridBuilder)
    {
        $this->datagridManagerLink = $datagridManagerLink;
        $this->datagridBuilder = $datagridBuilder;
    }

    public function onWidgetConfigurationLoad(WidgetConfigurationLoadEvent $event)
    {
        $configuration = $event->getConfiguration();
        if (!isset($configuration['route'], $configuration['route_parameters']['gridName'])
            || $configuration['route'] !== 'oro_dashboard_grid'
        ) {
            return;
        }
        $gridName = $configuration['route_parameters']['gridName'];

        // pass gridParams, grid may depend on them to be successfully built
        $gridParams = empty($configuration['route_parameters']['params'])
            ? []
            : $configuration['route_parameters']['params'];

        $gridConfiguration = $this->datagridManagerLink->getService()->getConfigurationForGrid($gridName);
        $datagrid = $this->datagridBuilder->build($gridConfiguration, new ParameterBag($gridParams));
        $metadata = $datagrid->getMetadata();

        $choices = $metadata->offsetGetByPath('[gridViews][views]', []);
        $viewChoices = [];
        foreach ($choices as $choice) {
            $viewChoices[$choice['label']] = $choice['name'];
        }
        if (!isset($configuration['fields'])) {
            $configuration['fields'] = [];
        }

        $configuration['configuration'] = array_merge(
            $configuration['configuration'],
            [
                'gridView' => [
                    'type' => ChoiceType::class,
                    'options' => [
                        'label' => 'oro.dashboard.grid.fields.grid_view.label',
                        'choices' => $viewChoices,
                    ],
                    'show_on_widget' => false,
                ]
            ]
        );

        $event->setConfiguration($configuration);
    }
}
