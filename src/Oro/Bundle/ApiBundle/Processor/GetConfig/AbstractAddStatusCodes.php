<?php

namespace Oro\Bundle\ApiBundle\Processor\GetConfig;

use Oro\Bundle\ApiBundle\Config\StatusCodeConfig;
use Oro\Bundle\ApiBundle\Config\StatusCodesConfig;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * The base class for processors that add HTTP status codes to the entity configuration.
 */
abstract class AbstractAddStatusCodes implements ProcessorInterface
{
    protected function addStatusCode(StatusCodesConfig $statusCodes, int $statusCode, string $description): void
    {
        if (!$statusCodes->hasCode($statusCode)) {
            $statusCodes->addCode($statusCode, $this->createStatusCode($description));
        }
    }

    protected function createStatusCode(string $description): StatusCodeConfig
    {
        $code = new StatusCodeConfig();
        $code->setDescription($description);

        return $code;
    }
}
