<?php

namespace Oro\Bundle\SoapBundle\Routing;

use Doctrine\Common\Inflector\Inflector;
use Oro\Component\Routing\Resolver\RouteCollectionAccessor;
use Oro\Component\Routing\Resolver\RouteOptionsResolverInterface;
use Symfony\Component\Routing\Route;

/**
 * As FOSRestBundle v1.7.1 generates a plural path for OPTIONS routes,
 * we need to add a singular path routes to avoid BC break.
 * The "old_options" option is added to the added singular path routes to mark them as deprecated.
 * @see \Oro\Bundle\SoapBundle\Routing\OldOptionsApiDocHandler
 */
class OldOptionsRouteOptionsResolver implements RouteOptionsResolverInterface
{
    /**
     * {@inheritdoc}
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function resolve(Route $route, RouteCollectionAccessor $routes)
    {
        if (!\in_array('OPTIONS', $route->getMethods(), true)) {
            return;
        }

        $entryPath = $this->getEntryPath($route);
        if (!$entryPath) {
            return;
        }
        $nameFromController = $this->getNameFromController($route);
        if (!$nameFromController) {
            return;
        }
        if (\str_replace('/', '', $entryPath) === $nameFromController) {
            return;
        }

        $pos = \strrpos($entryPath, '/');
        if (false !== $pos) {
            $pluralName = \substr($entryPath, $pos + 1);
            $singularName = \substr($nameFromController, $pos - \substr_count($entryPath, '/') + 1);
        } else {
            $pluralName = $entryPath;
            $singularName = $nameFromController;
        }
        if ($pluralName === $singularName || $pluralName !== Inflector::pluralize($singularName)) {
            return;
        }

        $singularPath  = \str_replace('/' . $pluralName, '/' . $singularName, $route->getPath());
        if (null === $routes->getByPath($singularPath, ['OPTIONS'])) {
            $singularRoute = $routes->cloneRoute($route);
            $singularRoute->setPath($singularPath);
            $singularRoute->setOption('old_options', true);
            $pluralRouteName = $routes->getName($route);
            $routes->insert(
                $routes->generateRouteName($pluralRouteName),
                $singularRoute,
                $pluralRouteName
            );
        }
    }

    /**
     * @param Route $route
     *
     * @return string
     */
    private function getEntryPath(Route $route)
    {
        $result = $route->getPath();
        $pos = \strpos($result, '{version}');
        if (false !== $pos) {
            $result = \substr($result, $pos + 10);
        }
        $pos = \strpos($result, '.{');
        if (false !== $pos) {
            $result = \substr($result, 0, $pos);
        }

        return $result;
    }

    /**
     * @param Route $route
     *
     * @return string
     */
    private function getNameFromController(Route $route)
    {
        $result = $route->getDefault('_controller');
        if (!empty($result)) {
            $pos = \strpos($result, 'Controller::');
            if (false !== $pos) {
                $result = \substr($result, 0, $pos);
            }
            $pos = \strrpos($result, '\\');
            if (false !== $pos) {
                $result = \substr($result, $pos + 1);
            }
            $result = \strtolower($result);
        }

        return $result;
    }
}
