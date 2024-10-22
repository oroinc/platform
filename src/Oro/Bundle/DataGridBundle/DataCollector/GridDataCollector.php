<?php

namespace Oro\Bundle\DataGridBundle\DataCollector;

use Oro\Bundle\DataGridBundle\Datagrid\ManagerInterface;
use Oro\Bundle\DataGridBundle\Datagrid\TraceableManager;
use Symfony\Component\EventDispatcher\Debug\TraceableEventDispatcher;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\DataCollector\DataCollector;
use Symfony\Component\HttpKernel\DataCollector\LateDataCollectorInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Symfony\Contracts\Service\ResetInterface;

/**
 * Data collector for datagrids
 */
class GridDataCollector extends DataCollector implements LateDataCollectorInterface
{
    private ?Request $currentRequest = null;

    public function __construct(
        private ManagerInterface $manager,
        private EventDispatcherInterface $eventDispatcher
    ) {
    }

    public function collect(Request $request, Response $response, ?\Throwable $exception = null)
    {
        $this->currentRequest = $request ?: null;
        $this->data = [];
    }

    public function reset()
    {
        parent::reset();

        if ($this->manager instanceof ResetInterface) {
            $this->manager->reset();
        }
    }

    public function getName()
    {
        return 'datagrids';
    }

    public function getDataGrids()
    {
        return $this->data['datagrids'];
    }

    public function getListeners()
    {
        return $this->data['listeners'];
    }

    public function getEvents()
    {
        return [
            \Oro\Bundle\DataGridBundle\Event\BuildBefore::NAME,
            \Oro\Bundle\DataGridBundle\Event\PreBuild::NAME,
            \Oro\Bundle\DataGridBundle\Event\BuildBefore::NAME,
            \Oro\Bundle\DataGridBundle\Event\BuildAfter::NAME,
            \Oro\Bundle\DataGridBundle\Event\OrmResultBefore::NAME,
            \Oro\Bundle\DataGridBundle\Event\OrmResultAfter::NAME,
            \Oro\Bundle\DataGridBundle\Event\OrmResultBeforeQuery::NAME,
            \Oro\Bundle\SearchBundle\Datagrid\Event\SearchResultAfter::NAME,
            \Oro\Bundle\SearchBundle\Datagrid\Event\SearchResultAfter::NAME
        ];
    }

    public function lateCollect()
    {
        if ($this->manager instanceof TraceableManager) {
            $this->data['datagrids'] = $this->cloneVar($this->manager->getDatagrids($this->currentRequest));
        }

        if (!$this->eventDispatcher instanceof TraceableEventDispatcher) {
            return;
        }
        $this->data['listeners'] = $this->cloneVar($this->eventDispatcher->getCalledListeners($this->currentRequest));
    }
}
