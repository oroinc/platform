<?php

namespace Oro\Bundle\LayoutBundle\Request;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Provides methods to check whether or not a web request is processed by the layout engine.
 */
class LayoutHelper
{
    /** @var RequestStack */
    private $requestStack;

    public function __construct(RequestStack $requestStack)
    {
        $this->requestStack = $requestStack;
    }

    public function isLayoutRequest(Request $request = null): bool
    {
        if (null === $request) {
            $request = $this->requestStack->getCurrentRequest();
        }

        return null !== $request && null !== $request->attributes->get('_layout');
    }

    public function isTemplateRequest(Request $request = null): bool
    {
        return !$this->isLayoutRequest($request);
    }
}
