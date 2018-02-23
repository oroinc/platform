<?php

namespace Oro\Bundle\ApiBundle\Processor\Config\GetConfig;

use Oro\Bundle\ApiBundle\Config\StatusCodeConfig;
use Oro\Bundle\ApiBundle\Config\StatusCodesConfig;
use Oro\Component\ChainProcessor\ProcessorInterface;

abstract class AbstractAddStatusCodes implements ProcessorInterface
{
    /**
     * @param StatusCodesConfig $statusCodes
     * @param int               $statusCode
     * @param string            $description
     */
    protected function addStatusCode(StatusCodesConfig $statusCodes, $statusCode, $description)
    {
        if (!$statusCodes->hasCode($statusCode)) {
            $statusCodes->addCode($statusCode, $this->createStatusCode($description));
        }
    }

    /**
     * @param string $description
     *
     * @return StatusCodeConfig
     */
    protected function createStatusCode($description)
    {
        $code = new StatusCodeConfig();
        $code->setDescription($description);

        return $code;
    }
}
