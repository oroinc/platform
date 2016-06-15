<?php

namespace Oro\Bundle\ApiBundle\ApiDoc;

use Symfony\Component\HttpFoundation\RequestStack;

class RestDocViewDetector
{
    const DEFAULT_VIEW = 'rest_json_api';

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
    public function getView()
    {
        $view = self::DEFAULT_VIEW;

        $request = $this->requestStack->getMasterRequest();
        if (null !== $request && $request->attributes->has('view')) {
            $view = $request->attributes->get('view');
        }

        return $view;
    }
}
