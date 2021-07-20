<?php

namespace Oro\Bundle\ActivityListBundle\Filter;

use Oro\Bundle\DataGridBundle\Datagrid\Builder as DatagridBuilder;
use Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface;
use Oro\Bundle\DataGridBundle\Datagrid\ParameterBag;
use Oro\Bundle\DataGridBundle\Event\BuildBefore;
use Oro\Bundle\QueryDesignerBundle\Grid\DatagridConfigurationBuilder;
use Oro\Bundle\QueryDesignerBundle\Model\AbstractQueryDesigner;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * The factory to create the "related-activity" datagrid used by
 * {@see \Oro\Bundle\ActivityListBundle\Filter\ActivityListFilter} to get ORM sub-query to filter data.
 */
class RelatedActivityDatagridFactory
{
    /** @var DatagridConfigurationBuilder */
    private $datagridConfigurationBuilder;

    /** @var DatagridBuilder */
    private $datagridBuilder;

    /** @var EventDispatcherInterface */
    private $eventDispatcher;

    public function __construct(
        DatagridConfigurationBuilder $datagridConfigurationBuilder,
        DatagridBuilder $datagridBuilder,
        EventDispatcherInterface $eventDispatcher
    ) {
        $this->datagridConfigurationBuilder = $datagridConfigurationBuilder;
        $this->datagridBuilder = $datagridBuilder;
        $this->eventDispatcher = $eventDispatcher;
    }

    public function createGrid(AbstractQueryDesigner $source): DatagridInterface
    {
        $this->datagridConfigurationBuilder->setGridName('related-activity');
        $this->datagridConfigurationBuilder->setSource($source);
        $config = $this->datagridConfigurationBuilder->getConfiguration();

        $stopPropagationListener = function (Event $e) {
            $e->stopPropagation();
        };

        $this->eventDispatcher->addListener(BuildBefore::NAME, $stopPropagationListener, 255);
        $grid = $this->datagridBuilder->build($config, new ParameterBag());
        $this->eventDispatcher->removeListener(BuildBefore::NAME, $stopPropagationListener);

        return $grid;
    }
}
