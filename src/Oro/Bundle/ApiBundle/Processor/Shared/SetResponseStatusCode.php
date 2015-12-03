<?php

namespace Oro\Bundle\ApiBundle\Processor\Shared;

use Oro\Bundle\ApiBundle\Processor\Context;
use Oro\Bundle\ApiBundle\Util\ExceptionUtil;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Symfony\Component\HttpFoundation\Response;

class SetResponseStatusCode implements ProcessorInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        /** @var $context Context */
        if (!$context->hasErrors()) {
            // if context has no errors
            return;
        }

        $statusCode = Response::HTTP_INTERNAL_SERVER_ERROR;
        $groupedStatuses = [];
        foreach ($context->getErrors() as $error) {
            $code = ExceptionUtil::getExceptionHttpCode($error->getInnerException());
            $parentCode = (int) floor($code / 100) * 100;
            if (!array_key_exists($parentCode, $groupedStatuses)) {
                $groupedStatuses[$parentCode] = [];
            }
            $groupedStatuses[$parentCode][] = $code;
        }

        if (count($groupedStatuses)) {
            $maxGroup = max(array_keys($groupedStatuses));
            $statusCode = $maxGroup;
            if (count($groupedStatuses[$maxGroup]) === 1) {
                $statusCode = array_pop($groupedStatuses[$maxGroup]);
            }
        }

        $context->setResponseStatusCode($statusCode);
    }
}
