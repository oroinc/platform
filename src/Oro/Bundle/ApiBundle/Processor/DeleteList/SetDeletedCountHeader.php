<?php

namespace Oro\Bundle\ApiBundle\Processor\DeleteList;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Bundle\ApiBundle\Processor\Context;

/**
 * Calculates and sets the total number of deleted records to "X-Include-Deleted-Count" response header,
 * in case if it was requested by "X-Include: deletedCount" request header.
 */
class SetDeletedCountHeader implements ProcessorInterface
{
    const RESPONSE_HEADER_NAME  = 'X-Include-Deleted-Count';
    const REQUEST_HEADER_VALUE = 'deletedCount';

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        /** @var DeleteListContext $context */

        if ($context->getResponseHeaders()->has(self::RESPONSE_HEADER_NAME)) {
            // the deleted records count header is already set
            return;
        }

        $xInclude = $context->getRequestHeaders()->get(Context::INCLUDE_HEADER);
        if (empty($xInclude) || !in_array(self::REQUEST_HEADER_VALUE, $xInclude, true)) {
            // the deleted records count is not requested
            return;
        }

        $result = $context->getResult();
        if (null !== $result && is_array($result)) {
            $context->getResponseHeaders()->set(self::RESPONSE_HEADER_NAME, count($result));
        }
    }
}
