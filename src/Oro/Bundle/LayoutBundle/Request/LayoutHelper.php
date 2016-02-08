<?php

namespace Oro\Bundle\LayoutBundle\Request;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

use Oro\Component\Layout\Exception\LogicException;
use Oro\Bundle\LayoutBundle\Annotation\Layout as LayoutAnnotation;

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
        if ($request === null) {
            $request = $this->requestStack->getCurrentRequest();
        }

        $layoutAnnotation = $this->getLayoutAnnotation($request);

        if ($layoutAnnotation && $request->attributes->get('_template')) {
            throw new LogicException(
                'The @Template() annotation cannot be used together with the @Layout() annotation.'
            );
        }

        return $layoutAnnotation !== null;
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
