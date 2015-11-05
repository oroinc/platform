<?php

namespace Oro\Bundle\SecurityBundle\EventListener\Datagrid;

use Symfony\Component\HttpFoundation\RequestStack;

use Oro\Bundle\DataGridBundle\Event\BuildBefore;

class ShareGridListener
{
    /** @var RequestStack */
    protected $requestStack;

    /**
     * @param RequestStack $requestStack
     */
    public function __construct(RequestStack $requestStack)
    {
        $this->requestStack = $requestStack;
    }

    /**
     * @param BuildBefore $event
     */
    public function onBuildBefore(BuildBefore $event)
    {
        $request = $this->requestStack->getCurrentRequest();
        if ($request) {
            $entityId = $request->get('entityId');
            $entityClass = $request->get('entityClass');
            $shareGridParams = $request->get('shared-datagrid');
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
}
