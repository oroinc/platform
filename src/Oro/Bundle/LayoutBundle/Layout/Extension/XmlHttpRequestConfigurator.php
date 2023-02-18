<?php

namespace Oro\Bundle\LayoutBundle\Layout\Extension;

use Oro\Component\Layout\ContextConfiguratorInterface;
use Oro\Component\Layout\ContextInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Adds is_xml_http_request parameter to the layout context.
 */
class XmlHttpRequestConfigurator implements ContextConfiguratorInterface
{
    public function __construct(private RequestStack $request, private array $routes = [])
    {
    }

    public function addRoute(string $route): void
    {
        if (!in_array($route, $this->routes, true)) {
            $this->routes[] = $route;
        }
    }

    public function setRoutes(array $routes): void
    {
        $this->routes = $routes;
    }

    public function configureContext(ContextInterface $context): void
    {
        if (!in_array($this->getRoute(), $this->routes, true)) {
            return;
        }

        $context->getResolver()
            ->define('is_xml_http_request')
            ->allowedTypes('bool')
            ->default((bool)$this->request->getCurrentRequest()?->isXmlHttpRequest());
    }

    private function getRoute(): ?string
    {
        $request = $this->request->getCurrentRequest();
        if ($request) {
            return $request->attributes->get('_route') ?: $request->attributes->get('_master_request_route');
        }

        return null;
    }
}
