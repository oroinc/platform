<?php

namespace Oro\Bundle\ApiBundle\Processor\Shared;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Bundle\ApiBundle\Collection\IncludedObjectData;
use Oro\Bundle\ApiBundle\Processor\Get\GetContext;
use Oro\Bundle\ApiBundle\Processor\RequestActionProcessor;
use Oro\Bundle\ApiBundle\Processor\ActionProcessorBagInterface;
use Oro\Bundle\ApiBundle\Processor\FormContext;
use Oro\Bundle\ApiBundle\Request\ApiActions;

/**
 * Loads included entities using "get" action.
 * We have to do it because the entities returned by "create" or "update" actions
 * must be the same as the entities returned by "get" action.
 */
class LoadNormalizedIncludedObjects implements ProcessorInterface
{
    /** @var ActionProcessorBagInterface */
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
        /** @var FormContext $context */

        $includedData = $context->getIncludedData();
        if (null === $includedData) {
            // there are no included data in the request
            return;
        }

        $includedObjects = $context->getIncludedObjects();
        if (null === $includedObjects) {
            // no included objects
            return;
        }

        foreach ($includedObjects as $object) {
            $this->processIncludedObject(
                $context,
                $includedObjects->getClass($object),
                $includedObjects->getId($object),
                $object,
                $includedObjects->getData($object)
            );
        }
    }

    /**
     * @param FormContext        $context
     * @param string             $objectClass
     * @param mixed              $objectId
     * @param object             $object
     * @param IncludedObjectData $objectData
     */
    protected function processIncludedObject(
        FormContext $context,
        $objectClass,
        $objectId,
        $object,
        IncludedObjectData $objectData
    ) {
        $getProcessor = $this->processorBag->getProcessor(ApiActions::GET);

        /** @var GetContext $getContext */
        $getContext = $getProcessor->createContext();
        $getContext->setVersion($context->getVersion());
        $getContext->getRequestType()->set($context->getRequestType());
        $getContext->setRequestHeaders($context->getRequestHeaders());
        $getContext->setClassName($objectClass);
        $getContext->setId($objectId);
        if (!$objectData->isExisting()) {
            $getContext->setResult($object);
        }
        $getContext->skipGroup('security_check');
        $getContext->skipGroup(RequestActionProcessor::NORMALIZE_RESULT_GROUP);
        $getContext->setSoftErrorsHandling(true);

        $getProcessor->process($getContext);

        if ($getContext->hasErrors()) {
            $errors = $getContext->getErrors();
            foreach ($errors as $error) {
                $context->addError($error);
            }
        } else {
            $objectData->setNormalizedData($getContext->getResult());
            $objectData->setMetadata($getContext->getMetadata());
        }
    }
}
