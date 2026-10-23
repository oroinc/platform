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
    private array $copiedHeaders = [];

    public function __construct(ContainerInterface $container, ApiRequestHelper $apiRequestHelper)
    {
        $this->container = $container;
        $this->apiRequestHelper = $apiRequestHelper;
    }

    /**
     * @param string[] $copiedHeaders
     */
    public function setCopiedHeaders(array $copiedHeaders): void
    {
        $this->copiedHeaders = $copiedHeaders;
    }

    #[\Override]
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
        $apiResponse = $actionHandler->handleUnhandledError(
            $request,
            $this->createUnauthorizedHttpException($response)
        );
        $this->copyHeaders($response, $apiResponse);
        $event->setResponse($apiResponse);
    }

    private function copyHeaders(Response $source, Response $target): void
    {
        foreach ($this->copiedHeaders as $name) {
            if ($source->headers->has($name) && !$target->headers->has($name)) {
                $target->headers->set($name, $source->headers->all($name));
            }
        }
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
