<?php

namespace Oro\Bundle\PlatformBundle\ErrorRenderer;

use Symfony\Component\ErrorHandler\ErrorRenderer\ErrorRendererInterface;
use Symfony\Component\ErrorHandler\Exception\FlattenException;
use Symfony\Component\HttpFoundation\Response;

/**
 * Fixes the response status code for JSON response to make sure that it corresponds
 * to the "code" property of the response body.
 * This is required because the status code for some custom exceptions can be changed via FOSRestBundle configuration,
 * but actually this configuration affects only the "code" property of the response body.
 * @link https://symfony.com/doc/current/bundles/FOSRestBundle/4-exception-controller-support.html
 */
class FixJsonStatusCodeErrorRenderer implements ErrorRendererInterface
{
    private ErrorRendererInterface $innerErrorRenderer;
    /** @var string[] */
    private array $jsonContentTypes;

    public function __construct(ErrorRendererInterface $innerErrorRenderer, array $jsonContentTypes)
    {
        $this->innerErrorRenderer = $innerErrorRenderer;
        $this->jsonContentTypes = $jsonContentTypes;
    }

    /**
     * {@inheritdoc}
     */
    public function render(\Throwable $exception): FlattenException
    {
        $flattenException = $this->innerErrorRenderer->render($exception);
        if ($this->isJsonResponse($flattenException)) {
            $statusCodeFromJsonResponseBody = $this->getStatusCodeFromJsonResponseBody($flattenException);
            if (null !== $statusCodeFromJsonResponseBody
                && $flattenException->getStatusCode() !== $statusCodeFromJsonResponseBody
                && isset(Response::$statusTexts[$statusCodeFromJsonResponseBody])
            ) {
                $flattenException->setStatusCode($statusCodeFromJsonResponseBody);
                $flattenException->setStatusText(Response::$statusTexts[$statusCodeFromJsonResponseBody]);
            }
        }

        return $flattenException;
    }

    private function isJsonResponse(FlattenException $exception): bool
    {
        $result = false;
        foreach ($exception->getHeaders() as $name => $value) {
            if ('Content-Type' === $name && \is_string($value) && \in_array($value, $this->jsonContentTypes, true)) {
                $result = true;
                break;
            }
        }

        return $result;
    }

    private function getStatusCodeFromJsonResponseBody(FlattenException $exception): ?int
    {
        $body = $exception->getAsString();
        if (!$body) {
            return null;
        }

        try {
            $decodedBody = json_decode($body, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            return null;
        }

        if (!$decodedBody || !\is_array($decodedBody)) {
            return null;
        }

        return $this->getStatusCodeFromDecodedBody($decodedBody);
    }

    private function getStatusCodeFromDecodedBody(array $decodedBody): ?int
    {
        $code = $decodedBody['code'] ?? null;
        if (!\is_int($code)) {
            if (\is_string($code) && is_numeric($code) && (((string)(int)$code) === $code)) {
                $code = (int)$code;
            } else {
                $code = null;
            }
        }

        return $code;
    }
}
