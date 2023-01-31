<?php

namespace Oro\Bundle\ApiBundle\Processor\Shared;

use Oro\Bundle\ApiBundle\Processor\Context;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * The base processor to detected the API request type.
 */
abstract class CheckRequestType implements ProcessorInterface
{
    public const OPERATION_NAME = 'check_request_type';

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context): void
    {
        /** @var Context $context */

        if ($context->isProcessed(self::OPERATION_NAME)) {
            // the request type was already detected
            return;
        }

        if ($this->checkRequestType($context)) {
            $context->setProcessed(self::OPERATION_NAME);
        }
    }

    /**
     * Tries to detect the request type.
     *
     * @param Context $context
     *
     * @return bool TRUE if the request type was detected; otherwise, FALSE
     */
    abstract protected function checkRequestType(Context $context): bool;
}
