<?php

namespace Oro\Bundle\LayoutBundle\Request;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

use Oro\Bundle\LayoutBundle\Annotation\Layout as LayoutAnnotation;

class LayoutHelper
{
    /**
     * @var RequestStack
     */
    protected $requestStack;

    /**
     * @var string
     */
    protected $environment;

    /**
     * @param RequestStack $requestStack
     * @param string|null $environment
     */
    public function __construct(RequestStack $requestStack, $environment = null)
    {
        $this->requestStack = $requestStack;
        $this->environment = $environment;
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

    /**
     * @return bool
     */
    public function isProfilerEnabled()
    {
        return $this->environment && in_array($this->environment, ['dev']);
    }
}
