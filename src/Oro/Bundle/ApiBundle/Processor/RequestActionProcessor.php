<?php

namespace Oro\Bundle\ApiBundle\Processor;

use Oro\Bundle\ApiBundle\Model\Error;
use Oro\Bundle\ApiBundle\Util\ExceptionUtil;
use Oro\Component\ChainProcessor\ActionProcessor;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\Exception\ExecutionFailedException;

class RequestActionProcessor extends ActionProcessor
{
    /**
     * {@inheritdoc}
     */
    protected function executeProcessors(ContextInterface $context)
    {
        try {
            parent::executeProcessors($context);
        } catch (ExecutionFailedException $e) {
            $error = new Error();
            $error->setStatus(ExceptionUtil::getExceptionHttpCode($e));
            $error->setDetail($e->getMessage());
            $error->setInnerException($e);
            $context->addError($error);

            $context->setFirstGroup('normalize_result');
            parent::executeProcessors($context);
        }
    }
}
