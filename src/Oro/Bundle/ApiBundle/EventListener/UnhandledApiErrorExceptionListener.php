<?php

namespace Oro\Bundle\ApiBundle\EventListener;

use Oro\Bundle\ApiBundle\Request\ApiRequestHelper;
use Oro\Bundle\ApiBundle\Request\Rest\RequestActionHandler;
use Psr\Container\ContainerInterface;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Contracts\Service\ServiceSubscriberInterface;

/**
 * Builds a response for case when an unexpected error happens before any public API action is started.
 */
class UnhandledApiErrorExceptionListener implements ServiceSubscriberInterface
{
    private ContainerInterface $container;
    private ApiRequestHelper $apiRequestHelper;

    public function __construct(ContainerInterface $container, ApiRequestHelper $apiRequestHelper)
    {
        $this->container = $container;
        $this->apiRequestHelper = $apiRequestHelper;
    }

    /**
     * {@inheritDoc}
     */
    public static function getSubscribedServices(): array
    {
        return [
            RequestActionHandler::class
        ];
    }

    public function onKernelException(ExceptionEvent $event): void
    {
        $request = $event->getRequest();
        if (!$this->apiRequestHelper->isApiRequest($request->getPathInfo())) {
            return;
        }

        /** @var RequestActionHandler $actionHandler */
        $actionHandler = $this->container->get(RequestActionHandler::class);
        $event->setResponse($actionHandler->handleUnhandledError($request, $event->getThrowable()));
    }
}
