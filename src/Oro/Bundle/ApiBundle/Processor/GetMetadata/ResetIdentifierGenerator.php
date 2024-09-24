<?php

namespace Oro\Bundle\ApiBundle\Processor\GetMetadata;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Makes a API resource as a resource without auto-generated identifier value.
 */
class ResetIdentifierGenerator implements ProcessorInterface
{
    #[\Override]
    public function process(ContextInterface $context): void
    {
        /** @var MetadataContext $context */

        $context->getResult()?->setHasIdentifierGenerator(false);
    }
}
