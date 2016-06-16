<?php

namespace Oro\Bundle\ApiBundle\ApiDoc;

use Symfony\Component\HttpFoundation\RequestStack;

class RestDocViewDetector
{
    /** @var RequestStack */
    protected $requestStack;

    /** @var string|null */
    protected $view;

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
        if (null === $this->view) {
            $request = $this->requestStack->getMasterRequest();
            $this->view = null !== $request && $request->attributes->has('view')
                ? $request->attributes->get('view')
                : '';
        }

        return $this->view;
    }

    /**
     * @param string|null $view
     */
    public function setView($view = null)
    {
        $this->view = $view;
    }
}
