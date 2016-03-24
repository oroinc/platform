<?php

namespace Oro\Bundle\ApiBundle\Processor\DeleteList;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

use Oro\Bundle\ApiBundle\Processor\Context;

/**
 * Calculates and sets the total number of deleted records to "X-Include-Delete-Count" response header,
 *  in case if it was requested by "X-Include: deleteCount" request header.
 */
class SetDeleteCountHeader implements ProcessorInterface
{
    const HEADER_NAME  = 'X-Include-Delete-Count';
    const HEADER_VALUE = 'deleteCount';

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        /** @var DeleteListContext $context */

        if ($context->getResponseHeaders()->has(self::HEADER_NAME)) {
            // total count header is already set
            return;
        }

        $xInclude = $context->getRequestHeaders()->get(Context::INCLUDE_HEADER);
        if (empty($xInclude) || !in_array(self::HEADER_VALUE, $xInclude, true)) {
            // total count is not requested
            return;
        }

        $totalCount = count($context->getResult());
        if (null !== $totalCount) {
            $context->getResponseHeaders()->set(self::HEADER_NAME, $totalCount);
        }
    }
}
