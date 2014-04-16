<?php

namespace Oro\Bundle\DashboardBundle\Model;

use Doctrine\Common\Collections\AbstractLazyCollection;
use Doctrine\Common\Collections\ArrayCollection;

use Oro\Bundle\DashboardBundle\Entity\Dashboard;

class WidgetsModelCollection extends AbstractLazyCollection
{
    /**
     * @var Dashboard
     */
    private $dashboard;

    /**
     * @var WidgetModelFactory
     */
    private $widgetModelFactory;

    public function __construct(Dashboard $dashboard, WidgetModelFactory $widgetModelFactory)
    {
        $this->dashboard = $dashboard;
        $this->widgetModelFactory = $widgetModelFactory;
    }

    /**
     * {@inheritdoc}
     */
    protected function doInitialize()
    {
        $this->collection = new ArrayCollection($this->widgetModelFactory->getModels($this->dashboard));
    }
}
