<?php

namespace Oro\Bundle\ApiBundle\Processor;

use Oro\Component\ChainProcessor\ActionProcessor;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\Exception\ExecutionFailedException;
use Oro\Bundle\ApiBundle\Model\Error;

class RequestActionProcessor extends ActionProcessor
{
    /**
     * {@inheritdoc}
     */
    protected function executeProcessors(ContextInterface $context)
    {
        /** @var Context $context */

        try {
            parent::executeProcessors($context);
        } catch (ExecutionFailedException $e) {
            // add an error to the context
            $error = new Error();
            $error->setInnerException($e);
            $context->addError($error);

            // go to the 'normalize_result' group that is intended
            // to prepare valid response of the current request type
            $context->setFirstGroup('normalize_result');
            parent::executeProcessors($context);
        }
    }
}
