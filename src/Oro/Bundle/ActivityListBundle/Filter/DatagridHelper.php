<?php

namespace Oro\Bundle\ActivityListBundle\Filter;

use Oro\Bundle\ActivityListBundle\Model\ActivityListQueryDesigner;
use Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface;
use Oro\Bundle\DataGridBundle\Datagrid\ParameterBag;
use Oro\Bundle\DataGridBundle\Event\BuildBefore;
use Oro\Bundle\QueryDesignerBundle\Grid\DatagridConfigurationBuilder;
use Oro\Component\DependencyInjection\ServiceLink;
use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class DatagridHelper
{
    /** @var DatagridConfigurationBuilder */
    protected $datagridConfigurationBuilder;

    /** @var ServiceLink */
    protected $gridBuilderLink;

    /** @var EventDispatcherInterface */
    protected $eventDispatcher;

    /**
     * @param DatagridConfigurationBuilder $datagridConfigurationBuilder
     * @param ServiceLink $gridBuilderLink
     * @param EventDispatcherInterface $eventDispatcher
     */
    public function __construct(
        DatagridConfigurationBuilder $datagridConfigurationBuilder,
        ServiceLink $gridBuilderLink,
        EventDispatcherInterface $eventDispatcher
    ) {
        $this->datagridConfigurationBuilder = $datagridConfigurationBuilder;
        $this->gridBuilderLink = $gridBuilderLink;
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * @param ActivityListQueryDesigner $source
     * @return DatagridInterface
     */
    public function createGrid(ActivityListQueryDesigner $source)
    {
        $this->datagridConfigurationBuilder->setGridName('related-activity');
        $this->datagridConfigurationBuilder->setSource($source);
        $config = $this->datagridConfigurationBuilder->getConfiguration();

        $stopPropagationListener = function (Event $e) {
            $e->stopPropagation();
        };

        $this->eventDispatcher->addListener(BuildBefore::NAME, $stopPropagationListener, 255);
        $grid = $this->gridBuilderLink->getService()->build($config, new ParameterBag());
        $this->eventDispatcher->removeListener(BuildBefore::NAME, $stopPropagationListener);

        return $grid;
    }
}
