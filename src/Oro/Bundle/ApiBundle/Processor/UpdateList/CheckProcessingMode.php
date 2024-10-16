<?php

namespace Oro\Bundle\ApiBundle\Processor\UpdateList;

use Oro\Bundle\ApiBundle\Exception\InvalidHeaderValueException;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Determines if a synchronous processing was requested via the "X-Mode" header equals to "sync".
 */
class CheckProcessingMode implements ProcessorInterface
{
    #[\Override]
    public function process(ContextInterface $context): void
    {
        /** @var UpdateListContext $context */

        if ($context->hasSynchronousMode()) {
            // the processing mode was already set
            return;
        }

        $mode = $context->getRequestHeaders()->get('X-Mode');
        if ($mode) {
            if ('sync' === $mode) {
                $context->setSynchronousMode(true);
            } elseif ('async' === $mode) {
                $context->setSynchronousMode(false);
            } else {
                throw new InvalidHeaderValueException('The accepted values for the "X-Mode" are "sync" or "async".');
            }
        } else {
            // no any specific processing mode was requested, set the mode as an asynchronous processing
            $context->setSynchronousMode(false);
        }
    }
}
