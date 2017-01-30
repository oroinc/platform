<?php

namespace Oro\Bundle\ReportBundle\Grid;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;

use Oro\Bundle\DataGridBundle\Extension\Export\ExportExtension;
use Oro\Bundle\EntityPaginationBundle\Datagrid\EntityPaginationExtension;
use Oro\Bundle\ReportBundle\Event\AfterBuildGridConfigurationEvent;

class ReportDatagridConfigurationBuilder extends BaseReportConfigurationBuilder
{
    /**
     * @var EventDispatcherInterface
     */
    protected $dispatcher;

    /**
     * {@inheritdoc}
     */
    public function getConfiguration()
    {
        $config = parent::getConfiguration();

        $config->offsetSetByPath('[source][acl_resource]', 'oro_report_view');
        $config->offsetSetByPath(ExportExtension::EXPORT_OPTION_PATH, true);
        $config->offsetSetByPath(EntityPaginationExtension::ENTITY_PAGINATION_PATH, true);

        if ($this->dispatcher instanceof EventDispatcherInterface
            && $this->dispatcher->hasListeners(AfterBuildGridConfigurationEvent::NAME)
        ) {
            $event = new AfterBuildGridConfigurationEvent(
                $config,
                $this->source,
                $this->converter->getDateGroupingAliases()
            );
            $this->dispatcher->dispatch($event::NAME, $event);
        }

        return $config;
    }

    /**
     * @param EventDispatcherInterface $dispatcher
     * @return $this
     */
    public function setDispatcher(EventDispatcherInterface $dispatcher)
    {
        $this->dispatcher = $dispatcher;

        return $this;
    }
}
