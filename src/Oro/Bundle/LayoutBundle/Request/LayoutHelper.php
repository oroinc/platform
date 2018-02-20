<?php

namespace Oro\Bundle\LayoutBundle\Request;

use Oro\Bundle\LayoutBundle\Annotation\Layout as LayoutAnnotation;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class LayoutHelper
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
     * @param Request|null $request
     * @return LayoutAnnotation
     */
    public function getLayoutAnnotation(Request $request = null)
    {
        if ($request === null) {
            $request = $this->requestStack->getCurrentRequest();
        }

        return $request->attributes->get('_layout');
    }

    /**
     * @param Request|null $request
     * @return bool
     */
    public function isLayoutRequest(Request $request = null)
    {
        return $this->getLayoutAnnotation($request) !== null;
    }

    /**
     * @param Request|null $request
     * @return bool
     */
    public function isTemplateRequest(Request $request = null)
    {
        return !$this->isLayoutRequest($request);
    }
}
