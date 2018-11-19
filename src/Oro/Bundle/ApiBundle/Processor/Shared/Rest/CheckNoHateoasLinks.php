<?php

namespace Oro\Bundle\ApiBundle\Processor\Shared\Rest;

use Oro\Bundle\ApiBundle\Processor\Context;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Checks if it was requested to exclude HATEOAS links from REST API response
 * via "X-Include: noHateoas" request header and if so, disables HATEOAS for the current request.
 */
class CheckNoHateoasLinks implements ProcessorInterface
{
    public const REQUEST_HEADER_VALUE = 'noHateoas';

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        /** @var Context $context */

        $xInclude = $context->getRequestHeaders()->get(Context::INCLUDE_HEADER);
        if (!empty($xInclude) && \in_array(self::REQUEST_HEADER_VALUE, $xInclude, true)) {
            $context->setHateoas(false);
        }
    }
}
