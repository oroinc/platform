<?php

namespace Oro\Bundle\ApiBundle\Processor;

use Oro\Component\ChainProcessor\ActionProcessor;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Bundle\ApiBundle\Model\Error;

class RequestActionProcessor extends ActionProcessor
{
    const NORMALIZE_RESULT_GROUP = 'normalize_result';

    /**
     * {@inheritdoc}
     */
    protected function executeProcessors(ContextInterface $context)
    {
        /** @var Context $context */

        $processors = $this->processorBag->getProcessors($context);

        try {
            /** @var ProcessorInterface $processor */
            foreach ($processors as $processor) {
                $processor->process($context);
            }
        } catch (\Exception $e) {
            // throw an exception was raised in normalize_result group as is
            // to avoid circular handling of such exception
            if (self::NORMALIZE_RESULT_GROUP === $processors->getGroup()) {
                throw $e;
            }

            // add an error to the context
            $error = new Error();
            $error->setInnerException($e);
            $context->addError($error);

            // go to the 'normalize_result' group that is intended
            // to prepare valid response of the current request type
            $context->setFirstGroup(self::NORMALIZE_RESULT_GROUP);
            $this->executeNormalizeResultProcessors($context);
        }
    }

    /**
     * @param ContextInterface $context
     */
    protected function executeNormalizeResultProcessors(ContextInterface $context)
    {
        $processors = $this->processorBag->getProcessors($context);
        /** @var ProcessorInterface $processor */
        foreach ($processors as $processor) {
            $processor->process($context);
        }
    }
}
