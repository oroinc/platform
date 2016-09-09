<?php

namespace Oro\Bundle\ApiBundle\Processor\Shared;

use Symfony\Component\HttpFoundation\Response;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Bundle\ApiBundle\Processor\Context;

/**
 * Sets the status code for the HTTP response.
 */
class SetHttpResponseStatusCode implements ProcessorInterface
{
    /** @var int */
    protected $defaultSuccessStatusCode;

    /**
     * @param int $defaultSuccessStatusCode
     */
    public function __construct($defaultSuccessStatusCode = Response::HTTP_OK)
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

        $statusCode = $this->defaultSuccessStatusCode;
        if ($context->hasErrors()) {
            $groupedCodes = [];
            foreach ($context->getErrors() as $error) {
                $code      = $error->getStatusCode() ?: Response::HTTP_INTERNAL_SERVER_ERROR;
                $groupCode = (int)floor($code / 100) * 100;

                if (!array_key_exists($groupCode, $groupedCodes)
                    || !in_array($code, $groupedCodes[$groupCode], true)
                ) {
                    $groupedCodes[$groupCode][] = $code;
                }
            }

            if (!empty($groupedCodes)) {
                $maxGroup   = max(array_keys($groupedCodes));
                $statusCode = $maxGroup;
                if (count($groupedCodes[$maxGroup]) === 1) {
                    $statusCode = array_pop($groupedCodes[$maxGroup]);
                }
            } else {
                $statusCode = Response::HTTP_INTERNAL_SERVER_ERROR;
            }
        }

        $context->setResponseStatusCode($statusCode);
    }
}
