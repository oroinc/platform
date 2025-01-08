<?php

namespace Oro\Bundle\NavigationBundle\Menu\Matcher\Voter;

use Knp\Menu\Matcher\Voter\UriVoter;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Voter based on the main request URI.
 */
class RequestVoter extends UriVoter
{
    public function __construct(RequestStack $requestStack)
    {
        // Use the main request, as sub-requests routes must not be taken into account when matching the menu items
        $request = $requestStack->getMainRequest();
        if ($request) {
            parent::__construct($request->getRequestUri());
        }
    }
}
