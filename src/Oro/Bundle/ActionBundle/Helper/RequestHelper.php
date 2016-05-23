<?php

namespace Oro\Bundle\ActionBundle\Helper;

use Symfony\Component\HttpFoundation\RequestStack;

class RequestHelper
{
    /** @var RequestStack */
    protected $requestStack;

    /** @var ApplicationsHelper */
    protected $applicationsHelper;

    /**
     * @param RequestStack $requestStack
     */
    public function __construct(RequestStack $requestStack, ApplicationsHelper $applicationsHelper)
    {
        $this->requestStack = $requestStack;
        $this->applicationsHelper = $applicationsHelper;
    }

    /**
     * @return string
     */
    public function getRequestRoute()
    {
        if (null === ($request = $this->requestStack->getMasterRequest())) {
            return;
        }

        $route = $request->get('_route');

        return $route !== $this->applicationsHelper->getExecutionRoute() ? $route : null;
    }
}
