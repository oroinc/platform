<?php

namespace Oro\Bundle\UIBundle\ContentProvider;

use Symfony\Component\HttpFoundation\RequestStack;

class CurrentRouteContentProvider extends AbstractContentProvider
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
     * {@inheritdoc}
     */
    public function getContent()
    {
        $request = $this->requestStack->getCurrentRequest();
        if ($request) {
            return $request->attributes->get('_master_request_route');
        }

        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'currentRoute';
    }
}
