<?php

namespace Oro\Bundle\ApiBundle\Processor\Shared;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

use Oro\Bundle\ApiBundle\Processor\SingleItemUpdateContext;

/**
 * Makes sure that the request data is not empty.
 */
class ValidateRequestData implements ProcessorInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        /** @var SingleItemUpdateContext $context */

        $requestData = $context->getRequestData();
        if (empty($requestData)) {
            throw new \RuntimeException('Request must have data.');
        }
    }
}
