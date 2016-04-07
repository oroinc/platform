<?php

namespace Oro\Bundle\ApiBundle\Processor\Shared;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Bundle\ApiBundle\Processor\ActionProcessorBagInterface;
use Oro\Bundle\ApiBundle\Processor\SingleItemContext;
use Oro\Bundle\ApiBundle\Processor\RequestActionProcessor;

/**
 * Loads whole entity by its id using "get" action.
 * We have to do it because the entity returned by "create" or "update" actions
 * must be the same as the entity returned by "get" action
 */
class LoadNormalizedEntity implements ProcessorInterface
{
    /** @var ActionProcessorBagInterface $processorBag */
    protected $processorBag;

    /**
     * @param ActionProcessorBagInterface $processorBag
     */
    public function __construct(ActionProcessorBagInterface $processorBag)
    {
        $this->processorBag = $processorBag;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        /** @var SingleItemContext $context */

        $entityId = $context->getId();
        if (null === $entityId) {
            // undefined entity
            return;
        }

        $getProcessor = $this->processorBag->getProcessor('get');

        /** @var SingleItemContext $getContext */
        $getContext = $getProcessor->createContext();
        $getContext->setVersion($context->getVersion());
        $getContext->getRequestType()->set($context->getRequestType()->toArray());
        $getContext->setClassName($context->getClassName());
        $getContext->setId($entityId);
        $getContext->skipGroup(RequestActionProcessor::NORMALIZE_RESULT_GROUP);

        $getProcessor->process($getContext);

        if ($getContext->hasErrors()) {
            $errors = $getContext->getErrors();
            foreach ($errors as $error) {
                $context->addError($error);
            }
        } else {
            $context->setResult($getContext->getResult());
        }
    }
}
