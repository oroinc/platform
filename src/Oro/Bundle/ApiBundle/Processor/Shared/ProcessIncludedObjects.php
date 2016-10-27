<?php

namespace Oro\Bundle\ApiBundle\Processor\Shared;

use Oro\Component\ChainProcessor\ActionProcessorInterface;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Bundle\ApiBundle\Model\Error;
use Oro\Bundle\ApiBundle\Processor\ActionProcessorBagInterface;
use Oro\Bundle\ApiBundle\Processor\FormContext;
use Oro\Bundle\ApiBundle\Processor\SingleItemContext;
use Oro\Bundle\ApiBundle\Request\ApiActions;
use Oro\Bundle\ApiBundle\Request\ErrorCompleterInterface;

/**
 * Validates and fill included objects.
 */
abstract class ProcessIncludedObjects implements ProcessorInterface
{
    /** @var ActionProcessorBagInterface */
    protected $processorBag;

    /** @var ErrorCompleterInterface */
    protected $errorCompleter;

    /**
     * @param ActionProcessorBagInterface $processorBag
     * @param ErrorCompleterInterface     $errorCompleter
     */
    public function __construct(
        ActionProcessorBagInterface $processorBag,
        ErrorCompleterInterface $errorCompleter
    ) {
        $this->processorBag = $processorBag;
        $this->errorCompleter = $errorCompleter;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        /** @var FormContext $context */

        $includedData = $context->getIncludedData();
        if (empty($includedData)) {
            // no included data
            return;
        }

        $includedObjects = $context->getIncludedObjects();
        if (null === $includedObjects) {
            // the Context does not have included objects
            return;
        }

        foreach ($includedObjects as $object) {
            $objectData = $includedObjects->getData($object);
            $action = $objectData->isExisting() ? ApiActions::UPDATE : ApiActions::CREATE;
            $this->processIncludedObject(
                $this->processorBag->getProcessor($action),
                $context,
                $includedObjects->getClass($object),
                $includedObjects->getId($object),
                $includedData[$objectData->getIndex()],
                $object,
                $objectData->getPath()
            );
        }
    }

    /**
     * @param ActionProcessorInterface $actionProcessor
     * @param FormContext              $context
     * @param string                   $objectClass
     * @param mixed                    $objectId
     * @param array                    $objectData
     * @param object                   $object
     * @param string                   $objectPath
     */
    protected function processIncludedObject(
        ActionProcessorInterface $actionProcessor,
        FormContext $context,
        $objectClass,
        $objectId,
        array $objectData,
        $object,
        $objectPath
    ) {
        /** @var SingleItemContext|FormContext $actionContext */
        $actionContext = $actionProcessor->createContext();
        $actionContext->setVersion($context->getVersion());
        $actionContext->getRequestType()->set($context->getRequestType());
        $actionContext->setRequestHeaders($context->getRequestHeaders());
        $actionContext->setIncludedObjects($context->getIncludedObjects());

        $actionContext->setClassName($objectClass);
        $actionContext->setId($objectId);
        $actionContext->setRequestData($objectData);
        $actionContext->setResult($object);

        $actionContext->setLastGroup('transform_data');
        $actionContext->setSoftErrorsHandling(true);

        $actionProcessor->process($actionContext);

        if ($actionContext->hasErrors()) {
            $actionMetadata = $actionContext->getMetadata();
            $errors = $actionContext->getErrors();
            foreach ($errors as $error) {
                $this->errorCompleter->complete($error, $actionMetadata);
                $this->fixErrorPath($error, $objectPath);
                $context->addError($error);
            }
        }
    }

    /**
     * @param Error  $error
     * @param string $objectPath
     */
    abstract protected function fixErrorPath(Error $error, $objectPath);
}
