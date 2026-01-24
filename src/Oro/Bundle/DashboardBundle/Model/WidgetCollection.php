<?php

namespace Oro\Bundle\DashboardBundle\Model;

use Doctrine\Common\Collections\AbstractLazyCollection;
use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\DashboardBundle\Entity\Dashboard;
use Oro\Bundle\DashboardBundle\Entity\Widget;

/**
 * Lazy-loading collection of visible widget models for a dashboard.
 *
 * This collection extends Doctrine's {@see AbstractLazyCollection} to provide on-demand loading
 * of widget models. It filters the dashboard's widgets to include only those that are
 * visible to the current user based on permissions and configuration, creating widget
 * models through the factory only when the collection is actually accessed. This approach
 * optimizes performance by avoiding unnecessary widget model creation.
 */
class WidgetCollection extends AbstractLazyCollection
{
    /**
     * @var Dashboard
     */
    private $dashboard;

    /**
     * @var Factory
     */
    private $factory;

    public function __construct(Dashboard $dashboard, Factory $factory)
    {
        $this->dashboard = $dashboard;
        $this->factory   = $factory;
    }

    #[\Override]
    protected function doInitialize()
    {
        $widgets = array();

        /** @var Widget $widget */
        foreach ($this->dashboard->getWidgets() as $widget) {
            $model = $this->factory->createVisibleWidgetModel($widget);
            if ($model) {
                $widgets[] = $model;
            }
        }

        $this->collection = new ArrayCollection($widgets);
    }
}
