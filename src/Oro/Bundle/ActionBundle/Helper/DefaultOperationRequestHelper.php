<?php

namespace Oro\Bundle\ActionBundle\Helper;

use Oro\Bundle\ActionBundle\Provider\RouteProviderInterface;
use Symfony\Component\HttpFoundation\RequestStack;

class DefaultOperationRequestHelper
{
    const DATAGRID_ROUTE = 'oro_datagrid_index';
    const MASS_ACTION_ROUTE = 'oro_datagrid_mass_action';
    const DATAGRID_WIDGET_ROUTE = 'oro_datagrid_widget';

    /** @var RequestStack */
    protected $requestStack;

    /** @var RouteProviderInterface */
    protected $routeProvider;

    /**
     * @param RequestStack $requestStack
     * @param RouteProviderInterface $routeProvider
     */
    public function __construct(RequestStack $requestStack, RouteProviderInterface $routeProvider)
    {
        $this->requestStack = $requestStack;
        $this->routeProvider = $routeProvider;
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

        if (in_array($route, [self::DATAGRID_ROUTE, self::MASS_ACTION_ROUTE, self::DATAGRID_WIDGET_ROUTE], true)) {
            $params = $request->query->get($request->get('gridName'));

            if (isset($params['originalRoute'])) {
                $route = $params['originalRoute'];
            }
        }

        return $route !== $this->routeProvider->getExecutionRoute() ? $route : null;
    }

    /**
     * @return bool
     */
    public function isExecutionRouteRequest()
    {
        if (null === ($request = $this->requestStack->getMasterRequest())) {
            return false;
        }

        $route = $request->get('_route');

        return $route === $this->routeProvider->getExecutionRoute();
    }
}
