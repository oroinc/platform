<?php

namespace Oro\Bundle\ActionBundle\Helper;

use Symfony\Component\HttpFoundation\RequestStack;

class DefaultOperationRequestHelper
{
    const DATAGRID_ROUTE = 'oro_datagrid_index';

    /** @var RequestStack */
    protected $requestStack;

    /** @var ApplicationsHelper */
    protected $applicationsHelper;

    /**
     * @param RequestStack $requestStack
     * @param ApplicationsHelper $applicationsHelper
     */
    public function __construct(RequestStack $requestStack, ApplicationsHelper $applicationsHelper)
    {
        $this->requestStack = $requestStack;
        $this->applicationsHelper = $applicationsHelper;
    }

    /**
     * @return string|null
     */
    public function getRequestRoute()
    {
        if (null === ($request = $this->requestStack->getMasterRequest())) {
            return null;
        }

        $route = $request->get('_route');

        if ($route === self::DATAGRID_ROUTE) {
            $params = $request->query->get($request->get('gridName'));

            if (isset($params['originalRoute'])) {
                $route = $params['originalRoute'];
            }
        }

        return $route !== $this->applicationsHelper->getExecutionRoute() ? $route : null;
    }
}
