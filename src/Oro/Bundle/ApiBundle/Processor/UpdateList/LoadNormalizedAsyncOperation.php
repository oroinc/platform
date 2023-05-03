<?php

namespace Oro\Bundle\ApiBundle\Processor\UpdateList;

use Oro\Bundle\ApiBundle\Entity\AsyncOperation;
use Oro\Bundle\ApiBundle\Processor\ActionProcessorBagInterface;
use Oro\Bundle\ApiBundle\Processor\Get\GetContext;
use Oro\Bundle\ApiBundle\Request\ApiAction;
use Oro\Bundle\ApiBundle\Request\ApiActionGroup;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Loads the created asynchronous operation using "get" action.
 */
class LoadNormalizedAsyncOperation implements ProcessorInterface
{
    private ActionProcessorBagInterface $processorBag;

    public function __construct(ActionProcessorBagInterface $processorBag)
    {
        $this->processorBag = $processorBag;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context): void
    {
        /** @var UpdateListContext $context */

        if ($context->hasResult()) {
            // the asynchronous operation was already added to the context
            return;
        }

        $operationId = $context->getOperationId();
        if (null !== $operationId) {
            $getProcessor = $this->processorBag->getProcessor(ApiAction::GET);

            /** @var GetContext $getContext */
            $getContext = $getProcessor->createContext();
            $getContext->setVersion($context->getVersion());
            $getContext->getRequestType()->set($context->getRequestType());
            $getContext->setRequestHeaders($context->getRequestHeaders());
            $getContext->setSharedData($context->getSharedData());
            $getContext->setHateoas($context->isHateoasEnabled());
            $getContext->setClassName(AsyncOperation::class);
            $getContext->setId($operationId);
            $getContext->skipGroup(ApiActionGroup::SECURITY_CHECK);
            $getContext->skipGroup(ApiActionGroup::DATA_SECURITY_CHECK);
            $getContext->skipGroup(ApiActionGroup::NORMALIZE_RESULT);
            $getContext->setSoftErrorsHandling(true);

            $getProcessor->process($getContext);

            if ($getContext->hasErrors()) {
                $errors = $getContext->getErrors();
                foreach ($errors as $error) {
                    $context->addError($error);
                }
            } else {
                $context->setConfig($getContext->getConfig());
                $context->setMetadata($getContext->getMetadata());
                $context->setResult($getContext->getResult());
            }
        }
    }
}
