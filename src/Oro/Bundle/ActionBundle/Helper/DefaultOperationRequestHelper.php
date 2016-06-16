<?php

namespace Oro\Bundle\ActionBundle\Helper;

use Symfony\Component\HttpFoundation\RequestStack;

class DefaultOperationRequestHelper
{
    const DATAGRID_ROUTE = 'oro_datagrid_index';
    const MASS_ACTION_ROUTE = 'oro_datagrid_mass_action';

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

        if (in_array($route, [self::DATAGRID_ROUTE, self::MASS_ACTION_ROUTE], true)) {
            $params = $request->query->get($request->get('gridName'));

            if (isset($params['originalRoute'])) {
                $route = $params['originalRoute'];
            }
        }

        return $route !== $this->applicationsHelper->getExecutionRoute() ? $route : null;
    }

    public function isExecutionRouteRequest()
    {
        if (null === ($request = $this->requestStack->getMasterRequest())) {
            return false;
        }

        $route = $request->get('_route');

        return $route === $this->applicationsHelper->getExecutionRoute();
    }
}
