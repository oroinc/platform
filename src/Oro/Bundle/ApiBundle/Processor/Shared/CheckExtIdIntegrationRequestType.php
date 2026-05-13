<?php

namespace Oro\Bundle\ApiBundle\Processor\Shared;

use Oro\Bundle\ApiBundle\Processor\Context;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Adds "ext_id" aspect to the request type if the "X-Integration-Type" header equals to "ext_id".
 */
class CheckExtIdIntegrationRequestType implements ProcessorInterface
{
    private const string REQUEST_HEADER_NAME = 'X-Integration-Type';
    private const string REQUEST_HEADER_VALUE = 'ext_id';
    private const string REQUEST_TYPE = 'ext_id';

    #[\Override]
    public function process(ContextInterface $context): void
    {
        /** @var Context $context */

        $requestType = $context->getRequestType();
        if (
            !$requestType->contains(self::REQUEST_TYPE)
            && self::REQUEST_HEADER_VALUE === $context->getRequestHeaders()->get(self::REQUEST_HEADER_NAME)
        ) {
            $requestType->add(self::REQUEST_TYPE);
        }
    }
}
