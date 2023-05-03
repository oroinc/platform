<?php

namespace Oro\Bundle\ApiBundle\Processor\Shared\Rest;

use Oro\Bundle\ApiBundle\Model\Error;
use Oro\Bundle\ApiBundle\Processor\Context;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

/**
 * Sets the "WWW-Authenticate" response header for the 401 (Unauthorized) HTTP response.
 */
class SetWwwAuthenticateResponseHeader implements ProcessorInterface
{
    private const WWW_AUTHENTICATE_HEADER = 'WWW-Authenticate';

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context): void
    {
        /** @var Context $context */

        $responseHeaders = $context->getResponseHeaders();
        if ($responseHeaders->has(self::WWW_AUTHENTICATE_HEADER)) {
            return;
        }

        $wwwAuthenticateHeader = $this->getWwwAuthenticateHeader($context->getErrors());
        if ($wwwAuthenticateHeader) {
            $responseHeaders->set(self::WWW_AUTHENTICATE_HEADER, $wwwAuthenticateHeader);
        }
    }

    /**
     * @param Error[] $errors
     *
     * @return string|null
     */
    private function getWwwAuthenticateHeader(array $errors): ?string
    {
        foreach ($errors as $error) {
            if ($error->getStatusCode() !== Response::HTTP_UNAUTHORIZED) {
                continue;
            }
            $exception = $error->getInnerException();
            if (!$exception instanceof HttpExceptionInterface) {
                continue;
            }
            $wwwAuthenticateHeader = $this->findWwwAuthenticateHeader($exception->getHeaders());
            if ($wwwAuthenticateHeader) {
                return $wwwAuthenticateHeader;
            }
        }

        return null;
    }

    private function findWwwAuthenticateHeader(array $headers): ?string
    {
        foreach ($headers as $name => $value) {
            if (strtolower($name) === 'www-authenticate') {
                return $value;
            }
        }

        return null;
    }
}
