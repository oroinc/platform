<?php

namespace Oro\Bundle\ApiBundle\Processor\Shared;

use Oro\Bundle\ApiBundle\Model\Error;
use Oro\Bundle\ApiBundle\Processor\Context;
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

    /** @var int */
    private $defaultSuccessStatusCode;

    /**
     * @param int $defaultSuccessStatusCode
     */
    public function __construct(int $defaultSuccessStatusCode = Response::HTTP_OK)
    {
        $this->defaultSuccessStatusCode = $defaultSuccessStatusCode;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        /** @var Context $context */

        if (null !== $context->getResponseStatusCode()) {
            // the status code is already set
            return;
        }

        $context->setResponseStatusCode($this->getResponseStatusCode($context));
    }

    /**
     * @param Context $context
     *
     * @return int
     */
    private function getResponseStatusCode(Context $context): int
    {
        $statusCode = $this->defaultSuccessStatusCode;
        if ($context->hasErrors()) {
            $statusCode = $this->computeErrorStatusCode($context->getErrors());
        } elseif (!$context->hasResult() && !$this->isResponseWithoutContent($statusCode)) {
            $statusCode = Response::HTTP_NO_CONTENT;
        }

        return $statusCode;
    }

    /**
     * {@inheritdoc}
     */
    private function isResponseWithoutContent(int $statusCode): bool
    {
        return
            Response::HTTP_NO_CONTENT === $statusCode
            || $this->isErrorResponseWithoutContent($statusCode);
    }

    /**
     * @param Error[] $errors
     *
     * @return int
     */
    private function computeErrorStatusCode(array $errors): int
    {
        $groupedCodes = [];
        foreach ($errors as $error) {
            $code = $error->getStatusCode() ?: Response::HTTP_INTERNAL_SERVER_ERROR;
            $groupCode = (int)\floor($code / 100) * 100;

            if (!\array_key_exists($groupCode, $groupedCodes)
                || !\in_array($code, $groupedCodes[$groupCode], true)
            ) {
                $groupedCodes[$groupCode][] = $code;
            }
        }

        $statusCode = Response::HTTP_INTERNAL_SERVER_ERROR;
        if (!empty($groupedCodes)) {
            $maxGroup = \max(\array_keys($groupedCodes));
            $statusCode = $maxGroup;
            if (\count($groupedCodes[$maxGroup]) === 1) {
                $statusCode = \array_pop($groupedCodes[$maxGroup]);
            }
        }

        return $statusCode;
    }
}
