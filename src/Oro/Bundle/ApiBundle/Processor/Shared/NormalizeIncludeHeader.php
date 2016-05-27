<?php

namespace Oro\Bundle\ApiBundle\Processor\Shared;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Bundle\ApiBundle\Processor\Context;

/**
 * Splits the value of "X-Include" request header into an array by ";".
 */
class NormalizeIncludeHeader implements ProcessorInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        /** @var Context $context */

        $xInclude = $context->getRequestHeaders()->get(Context::INCLUDE_HEADER);
        if (empty($xInclude) || is_array($xInclude)) {
            // no X-Include header or it is already normalized
            return;
        }

        $context->getRequestHeaders()->set(
            Context::INCLUDE_HEADER,
            array_filter(array_map('trim', explode(';', $xInclude)))
        );
    }
}
