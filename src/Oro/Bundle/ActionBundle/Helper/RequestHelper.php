<?php

namespace Oro\Bundle\ActionBundle\Helper;

use Symfony\Component\HttpFoundation\RequestStack;

class RequestHelper
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
     * @return string
     */
    public function getMasterRequestRoute()
    {
        if (null === ($request = $this->requestStack->getMasterRequest())) {
            return;
        }

        return $request->get('_route');
    }
}
