<?php

namespace Oro\Bundle\UIBundle\Provider;

use Symfony\Component\HttpFoundation\RequestStack;

class WidgetContextProvider
{
    /**
     * @var RequestStack
     */
    protected $requestStack;

    /**
     * @param RequestStack $requestStack
     */
    public function __construct(RequestStack $requestStack)
    {
        $this->requestStack = $requestStack;
    }

    /**
     * Returns if working in scope of widget
     *
     * @return bool
     */
    public function isActive()
    {
        $request = $this->requestStack->getCurrentRequest();
        if ($request) {
            return (bool) $request->get('_wid', false);
        }

        return false;
    }

    /**
     * Returns widget identifier
     *
     * @return bool|string
     */
    public function getWid()
    {
        $request = $this->requestStack->getCurrentRequest();
        if ($request) {
            return $request->get('_wid', false);
        }

        return false;
    }
}
