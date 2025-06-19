<?php

namespace Oro\Bundle\ApiBundle\Processor\Shared;

use Oro\Bundle\ApiBundle\Metadata\EntityMetadata;
use Oro\Bundle\ApiBundle\Processor\Context;
use Oro\Bundle\ApiBundle\Processor\FormContext;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Disables the full mode for getting entity metadata.
 */
class DisableFullMetadataMode implements ProcessorInterface
{
    #[\Override]
    public function process(ContextInterface $context): void
    {
        /** @var Context $context */

        $this->getMetadata($context)?->setEntityMetadataFullMode(false);
    }

    private function getMetadata(Context $context): ?EntityMetadata
    {
        try {
            return $context instanceof FormContext ? $context->getNormalizedMetadata() : $context->getMetadata();
        } catch (\Throwable $e) {
            return null;
        }
    }
}
