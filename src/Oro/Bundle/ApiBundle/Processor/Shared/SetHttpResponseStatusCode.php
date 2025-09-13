<?php

namespace Oro\Bundle\ApiBundle\Processor\Shared;

use Oro\Bundle\ApiBundle\Model\Error;
use Oro\Bundle\ApiBundle\Processor\Context;
use Oro\Bundle\ApiBundle\Request\ErrorResponseStatusCodeTrait;
use Oro\Bundle\ApiBundle\Request\ErrorStatusCodesWithoutContentTrait;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * Sets the status code for the HTTP response.
 */
class SetHttpResponseStatusCode implements ProcessorInterface
{
    use ErrorStatusCodesWithoutContentTrait;
    use ErrorResponseStatusCodeTrait;

    private int $defaultSuccessStatusCode;

    public function __construct(int $defaultSuccessStatusCode = Response::HTTP_OK)
    {
        $this->defaultSuccessStatusCode = $defaultSuccessStatusCode;
    }

    /**
     * {@inheritDoc}
     */
    public function process(ContextInterface $context): void
    {
        /** @var Context $context */

        if (null !== $context->getResponseStatusCode()) {
            // the status code is already set
            return;
        }

        $context->setResponseStatusCode($this->getResponseStatusCode($context));
    }

    private function getResponseStatusCode(Context $context): int
    {
        $statusCode = $this->defaultSuccessStatusCode;
        if ($context->hasErrors()) {
            $statusCode = $this->computeResponseStatusCode(array_map(static function (Error $error): int {
                return $error->getStatusCode() ?: Response::HTTP_INTERNAL_SERVER_ERROR;
            }, $context->getErrors()));
        } elseif (!$context->hasResult() && !$this->isResponseWithoutContent($statusCode)) {
            $statusCode = Response::HTTP_NO_CONTENT;
        }

        return $statusCode;
    }

    private function isResponseWithoutContent(int $statusCode): bool
    {
        return
            Response::HTTP_NO_CONTENT === $statusCode
            || $this->isErrorResponseWithoutContent($statusCode);
    }
}
