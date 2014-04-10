<?php

namespace Oro\Bundle\DashboardBundle\Provider;

use Oro\Bundle\DashboardBundle\Entity\Repository\DashboardWidgetRepository;
use Oro\Bundle\DashboardBundle\Model\WidgetModelFactory;

class WidgetModelProvider
{

    /**
     * @var DashboardWidgetRepository
     */
    protected $widgetRepository;

    /**
     * @var WidgetModelFactory
     */
    protected $widgetModelFactory;

    public function __construct(DashboardWidgetRepository $widgetRepository, WidgetModelFactory $widgetModelFactory)
    {
        $this->widgetRepository = $widgetRepository;
        $this->widgetModelFactory = $widgetModelFactory;
    }

    public function getAvailableWidgets()
    {
        $widgets = $this->widgetRepository->getAvailableWidgets();

        $widgetModels = array();

        foreach ($widgets as $widget) {
            $widgetModels[] = $this->widgetModelFactory->getModel($widget);
        }

        return $widgetModels;
    }
}
