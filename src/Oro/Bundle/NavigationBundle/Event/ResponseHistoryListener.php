<?php

namespace Oro\Bundle\NavigationBundle\Event;

use Oro\Bundle\NavigationBundle\Utils\NavigationHistoryLogger;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Psr\Container\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Contracts\Service\ServiceSubscriberInterface;

/**
 * Adds the current web request to the navigation history if it represents the navigation history item.
 */
class ResponseHistoryListener implements ServiceSubscriberInterface
{
    /** @var TokenAccessorInterface */
    private $tokenAccessor;

    /** @var string */
    private $userEntityClass;

    /** @var ContainerInterface */
    private $container;

    /** @var array [route name => true, ...] */
    private $excludedRoutes = [];

    public function __construct(
        TokenAccessorInterface $tokenAccessor,
        string $userEntityClass,
        ContainerInterface $container
    ) {
        $this->tokenAccessor = $tokenAccessor;
        $this->userEntityClass = $userEntityClass;
        $this->container = $container;
    }

    /**
     * {@inheritDoc}
     */
    public static function getSubscribedServices()
    {
        return [
            'oro_navigation.navigation_history_logger' => NavigationHistoryLogger::class
        ];
    }

    /**
     * Adds a route to the list of routes that should not be added to the navigation history.
     */
    public function addExcludedRoute(string $routeName): void
    {
        $this->excludedRoutes[$routeName] = true;
    }

    public function onResponse(FilterResponseEvent $event)
    {
        if (!$event->isMasterRequest()) {
            return;
        }

        if (!$this->tokenAccessor->getUser() instanceof $this->userEntityClass) {
            return;
        }

        $request = $event->getRequest();
        if ($this->canAddToHistory($request, $event->getResponse())) {
            /** @var NavigationHistoryLogger $navigationHistoryLogger */
            $navigationHistoryLogger = $this->container->get('oro_navigation.navigation_history_logger');
            $navigationHistoryLogger->logRequest($request);
        }
    }

    /**
     * Checks if a current request can be added to a history.
     */
    private function canAddToHistory(Request $request, Response $response): bool
    {
        $result =
            $response->getStatusCode() === 200
            && $this->isSupportedRoute($request)
            && $request->getRequestFormat() === 'html'
            && $request->getMethod() === 'GET'
            && (
                !$request->isXmlHttpRequest()
                || $request->headers->get(ResponseHashnavListener::HASH_NAVIGATION_HEADER)
            );

        if ($result && $response->headers->has('Content-Disposition')) {
            $contentDisposition = $response->headers->get('Content-Disposition');
            $result =
                strpos($contentDisposition, ResponseHeaderBag::DISPOSITION_INLINE) !== 0
                && strpos($contentDisposition, ResponseHeaderBag::DISPOSITION_ATTACHMENT) !== 0;
        }

        return $result;
    }

    private function isSupportedRoute(Request $request): bool
    {
        $route = $request->attributes->get('_route');

        return
            $route
            && '_' !== $route[0]
            && !isset($this->excludedRoutes[$route]);
    }
}
