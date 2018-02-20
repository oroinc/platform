<?php

namespace Oro\Bundle\DashboardBundle\Model;

use Doctrine\Common\Collections\AbstractLazyCollection;
use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\DashboardBundle\Entity\Dashboard;
use Oro\Bundle\DashboardBundle\Entity\Widget;

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

    /**
     * @param Dashboard $dashboard
     * @param Factory $factory
     */
    public function __construct(Dashboard $dashboard, Factory $factory)
    {
        $this->dashboard = $dashboard;
        $this->factory   = $factory;
    }

    /**
     * {@inheritdoc}
     */
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
