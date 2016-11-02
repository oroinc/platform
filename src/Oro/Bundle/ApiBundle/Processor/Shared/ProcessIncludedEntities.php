<?php

namespace Oro\Bundle\ApiBundle\Processor\Shared;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Bundle\ApiBundle\Collection\IncludedEntityData;
use Oro\Bundle\ApiBundle\Model\Error;
use Oro\Bundle\ApiBundle\Processor\ActionProcessorBagInterface;
use Oro\Bundle\ApiBundle\Processor\FormContext;
use Oro\Bundle\ApiBundle\Processor\SingleItemContext;
use Oro\Bundle\ApiBundle\Request\ApiActions;
use Oro\Bundle\ApiBundle\Request\ErrorCompleterInterface;

/**
 * Validates and fill included entities.
 */
abstract class ProcessIncludedEntities implements ProcessorInterface
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

        $includedEntities = $context->getIncludedEntities();
        if (null === $includedEntities) {
            // the Context does not have included entities
            return;
        }

        foreach ($includedEntities as $entity) {
            $entityData = $includedEntities->getData($entity);
            $this->processIncludedEntity(
                $context,
                $includedEntities->getClass($entity),
                $includedEntities->getId($entity),
                $includedData[$entityData->getIndex()],
                $entity,
                $entityData
            );
        }
    }

    /**
     * @param FormContext              $context
     * @param string                   $entityClass
     * @param mixed                    $entityId
     * @param array                    $entityRequestData
     * @param object                   $entity
     * @param IncludedEntityData       $entityData
     */
    protected function processIncludedEntity(
        FormContext $context,
        $entityClass,
        $entityId,
        array $entityRequestData,
        $entity,
        IncludedEntityData $entityData
    ) {
        $actionProcessor = $this->processorBag->getProcessor(
            $entityData->isExisting() ? ApiActions::UPDATE : ApiActions::CREATE
        );

        /** @var SingleItemContext|FormContext $actionContext */
        $actionContext = $actionProcessor->createContext();
        $actionContext->setVersion($context->getVersion());
        $actionContext->getRequestType()->set($context->getRequestType());
        $actionContext->setRequestHeaders($context->getRequestHeaders());
        $actionContext->setIncludedEntities($context->getIncludedEntities());

        $actionContext->setClassName($entityClass);
        $actionContext->setId($entityId);
        $actionContext->setRequestData($entityRequestData);
        $actionContext->setResult($entity);

        $actionContext->setLastGroup('transform_data');
        $actionContext->setSoftErrorsHandling(true);

        $actionProcessor->process($actionContext);

        if ($actionContext->hasErrors()) {
            $actionMetadata = $actionContext->getMetadata();
            $errors = $actionContext->getErrors();
            foreach ($errors as $error) {
                $this->errorCompleter->complete($error, $actionMetadata);
                $this->fixErrorPath($error, $entityData->getPath());
                $context->addError($error);
            }
        } else {
            $entityData->setMetadata($actionContext->getMetadata());
        }
    }

    /**
     * @param Error  $error
     * @param string $entityPath
     */
    abstract protected function fixErrorPath(Error $error, $entityPath);
}
