<?php

namespace Oro\Bundle\SoapBundle\Routing;

use Doctrine\Common\Inflector\Inflector;

use Symfony\Component\Routing\Route;

use Oro\Component\Routing\Resolver\RouteCollectionAccessor;
use Oro\Component\Routing\Resolver\RouteOptionsResolverInterface;

/**
 * As FOSRestBundle v1.7.1 generates a plural path for OPTIONS routes,
 * we need to add a single path to avoid BC break.
 * The single path is marked as deprecated.
 *
 * @deprecated since 1.8. Will be removed in 2.0
 */
class OldOptionsRouteOptionsResolver implements RouteOptionsResolverInterface
{
    /**
     * {@inheritdoc}
     */
    public function resolve(Route $route, RouteCollectionAccessor $routes)
    {
        if (!in_array('OPTIONS', $route->getMethods(), true)) {
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

        $nameFromPath = str_replace('/', '', $entryPath);
        if ($nameFromPath === $nameFromController) {
            return;
        }

        if (false !== $pos = strrpos($entryPath, '/')) {
            $pluralName = substr($entryPath, $pos + 1);
            $singleName = substr($nameFromController, $pos - substr_count($entryPath, '/') + 1);
        } else {
            $pluralName = $entryPath;
            $singleName = $nameFromController;
        }
        if ($pluralName === $singleName || $pluralName !== Inflector::pluralize($singleName)) {
            return;
        }

        $singlePath  = str_replace('/' . $pluralName, '/' . $singleName, $route->getPath());
        $singleRoute = $routes->cloneRoute($route);
        $singleRoute->setPath($singlePath);
        $singleRoute->setOption('old_options', true);
        $pluralRouteName = $routes->getName($route);
        $routes->insert(
            $routes->generateRouteName($pluralRouteName),
            $singleRoute,
            $pluralRouteName
        );
    }

    /**
     * @param Route $route
     *
     * @return string
     */
    protected function getEntryPath(Route $route)
    {
        $result = $route->getPath();
        if (false !== $pos = strpos($result, '{version}')) {
            $result = substr($result, $pos + 10);
        }
        if (false !== $pos = strpos($result, '.{')) {
            $result = substr($result, 0, $pos);
        }

        return $result;
    }

    /**
     * @param Route $route
     *
     * @return string
     */
    protected function getNameFromController(Route $route)
    {
        $result = $route->getDefault('_controller');
        if (!empty($result)) {
            if (false !== $pos = strpos($result, 'Controller::')) {
                $result = substr($result, 0, $pos);
            }
            if (false !== $pos = strrpos($result, '\\')) {
                $result = substr($result, $pos + 1);
            }
            $result = strtolower($result);
        }

        return $result;
    }
}
