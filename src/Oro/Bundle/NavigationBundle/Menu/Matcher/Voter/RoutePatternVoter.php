<?php

namespace Oro\Bundle\NavigationBundle\Menu\Matcher\Voter;

use Knp\Menu\ItemInterface;
use Knp\Menu\Matcher\Voter\VoterInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Voter based on route patterns and route parameters
 */
class RoutePatternVoter implements VoterInterface
{
    private RequestStack $requestStack;

    public function __construct(RequestStack $requestStack)
    {
        $this->requestStack = $requestStack;
    }

    public function matchItem(ItemInterface $item): ?bool
    {
        // Using master request, as sub-requests routes must not be taken into account when matching the menu items
        $request = $this->requestStack->getMainRequest();

        if (null === $request) {
            return null;
        }

        $route = $request->attributes->get('_route');
        if (null === $route) {
            return null;
        }

        $routes = (array) $item->getExtra('routes', []);
        $parameters = (array) $item->getExtra('routesParameters', []);

        foreach ($routes as $testedRoute) {
            if (!$this->routeMatch($testedRoute, $route)) {
                continue;
            }

            if (isset($parameters[$testedRoute]) && !$this->parametersMatch($parameters[$testedRoute], $request)) {
                return null;
            }

            return true;
        }

        return null;
    }

    /**
     * Returns TRUE if route matches pattern.
     *
     * Pattern could be:
     *   - full route name - "oro_user_create"
     *   - a regular expression string - "/^oro_user_\w+$/"
     *   - a string with asterisks - "oro_user_*"
     *
     * @param string $pattern
     * @param string $actualRoute
     * @return boolean
     */
    protected function routeMatch($pattern, $actualRoute)
    {
        if ($pattern == $actualRoute) {
            return true;
        } elseif (str_starts_with($pattern, '/') && strlen($pattern) - 1 === strrpos($pattern, '/')) {
            return preg_match($pattern, $actualRoute);
        } elseif (str_contains($pattern, '*')) {
            $pattern = sprintf('/^%s$/', str_replace('*', '\w+', $pattern));
            return preg_match($pattern, $actualRoute);
        } else {
            return false;
        }
    }

    /**
     * Returns TRUE if request matches parameters
     */
    protected function parametersMatch(array $parameters, Request $request): bool
    {
        foreach ($parameters as $name => $value) {
            if ($request->attributes->get($name) != $value) {
                return false;
            }
        }
        return true;
    }
}
