<?php

namespace Oro\Bundle\ApiBundle\EventListener;

use Oro\Bundle\ApiBundle\Request\ApiRequestHelper;
use Oro\Bundle\ApiBundle\Request\Rest\RequestActionHandler;
use Psr\Container\ContainerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Contracts\Service\ServiceSubscriberInterface;

/**
 * Builds a response for an unauthorized API requests.
 */
class UnauthorizedApiRequestListener implements ServiceSubscriberInterface
{
    private const WWW_AUTHENTICATE_HEADER = 'WWW-Authenticate';

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

    public function onKernelResponse(ResponseEvent $event): void
    {
        $response = $event->getResponse();
        if (Response::HTTP_UNAUTHORIZED !== $response->getStatusCode()) {
            return;
        }

        $request = $event->getRequest();
        if (!$this->apiRequestHelper->isApiRequest($request->getPathInfo())) {
            return;
        }

        /** @var RequestActionHandler $actionHandler */
        $actionHandler = $this->container->get(RequestActionHandler::class);
        $event->setResponse($actionHandler->handleUnhandledError(
            $request,
            $this->createUnauthorizedHttpException($response)
        ));
    }

    private function createUnauthorizedHttpException(Response $response): HttpException
    {
        $headers = [];
        if ($response->headers->has(self::WWW_AUTHENTICATE_HEADER)) {
            $headers[self::WWW_AUTHENTICATE_HEADER] = $response->headers->get(self::WWW_AUTHENTICATE_HEADER);
        }

        return new HttpException(Response::HTTP_UNAUTHORIZED, $response->getContent() ?: '', null, $headers, 0);
    }
}
