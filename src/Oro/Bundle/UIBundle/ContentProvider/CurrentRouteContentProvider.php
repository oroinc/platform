<?php

namespace Oro\Bundle\UIBundle\ContentProvider;

use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Returns the master request route name.
 */
class CurrentRouteContentProvider implements ContentProviderInterface
{
    /** @var RequestStack */
    private $requestStack;

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
        if (null === $request) {
            return null;
        }

        return $request->attributes->get('_master_request_route');
    }
}
