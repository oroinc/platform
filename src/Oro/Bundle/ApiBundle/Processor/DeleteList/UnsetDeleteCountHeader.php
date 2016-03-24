<?php

namespace Oro\Bundle\ApiBundle\Processor\DeleteList;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Unset the "X-Include-Delete-Count" response header
 *  in case if it was requested by "X-Include: deleteCount" request header but an error occurred in deletion process.
 */
class UnsetDeleteCountHeader implements ProcessorInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        /** @var DeleteListContext $context */

        if (!$context->getResponseHeaders()->has(SetDeleteCountHeader::HEADER_NAME)) {
            // delete count header was not set
            return;
        }

        if ($context->hasResult()) {
            $context->getResponseHeaders()->remove(SetDeleteCountHeader::HEADER_NAME);
        }
    }
}
