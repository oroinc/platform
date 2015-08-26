<?php

namespace Oro\Bundle\SecurityBundle\EventListener\Datagrid;

use Symfony\Component\HttpFoundation\Request;

use Oro\Bundle\DataGridBundle\Event\BuildBefore;

class ShareGridListener
{
    /** @var Request */
    protected $request;

    /**
     * @param Request $request
     */
    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    /**
     * @param BuildBefore $event
     */
    public function onBuildBefore(BuildBefore $event)
    {
        $entityId = $this->request->get('entityId');
        $entityClass = $this->request->get('entityClass');
        $shareGridParams = $this->request->get('share-grid');
        if ($shareGridParams) {
            $entityId = $shareGridParams['entityId'];
            $entityClass = $shareGridParams['entityClass'];
        }
        $event->getDatagrid()->getParameters()->add([
            'entityId' => $entityId,
            'entityClass' => $entityClass,
        ]);
    }
}
