<?php

namespace Oro\Bundle\ApiBundle\Processor\Shared;

use Oro\Bundle\ApiBundle\Collection\IncludedEntityData;
use Oro\Bundle\ApiBundle\Model\Error;
use Oro\Bundle\ApiBundle\Processor\ActionProcessorBagInterface;
use Oro\Bundle\ApiBundle\Processor\FormContext;
use Oro\Bundle\ApiBundle\Processor\SingleItemContext;
use Oro\Bundle\ApiBundle\Request\ApiActionGroup;
use Oro\Bundle\ApiBundle\Request\ErrorCompleterRegistry;
use Oro\Bundle\ApiBundle\Request\ErrorStatusCodesWithoutContentTrait;
use Oro\Bundle\ApiBundle\Request\ExceptionTextExtractorInterface;
use Oro\Component\ChainProcessor\ActionProcessorInterface;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * Validates and fill included entities.
 */
class ProcessIncludedEntities implements ProcessorInterface
{
    use ErrorStatusCodesWithoutContentTrait;

    private ActionProcessorBagInterface $processorBag;
    private ErrorCompleterRegistry $errorCompleterRegistry;
    private ExceptionTextExtractorInterface $exceptionTextExtractor;

    public function __construct(
        ActionProcessorBagInterface $processorBag,
        ErrorCompleterRegistry $errorCompleterRegistry,
        ExceptionTextExtractorInterface $exceptionTextExtractor
    ) {
        $this->processorBag = $processorBag;
        $this->errorCompleterRegistry = $errorCompleterRegistry;
        $this->exceptionTextExtractor = $exceptionTextExtractor;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context): void
    {
        /** @var FormContext $context */

        if (!$context->getIncludedData()) {
            // no included data
            return;
        }

        $includedEntities = $context->getIncludedEntities();
        if (null === $includedEntities) {
            // the context does not have included entities
            return;
        }

        $entityMapper = $context->getEntityMapper();
        if (null !== $entityMapper) {
            foreach ($includedEntities as $entity) {
                $entityMapper->registerEntity($entity);
            }
        }

        $this->processIncludedEntities($context);
    }

    private function processIncludedEntities(FormContext $context): void
    {
        // normalize request data for all included entities
        $processingIncludedEntities = [];
        $includedData = $context->getIncludedData();
        $includedEntities = $context->getIncludedEntities();
        foreach ($includedEntities as $entity) {
            /** @var IncludedEntityData $entityData */
            $entityData = $includedEntities->getData($entity);
            $actionProcessor = $this->processorBag->getProcessor($entityData->getTargetAction());
            $actionContext = $this->createIncludedEntityProcessingContext(
                $actionProcessor,
                $context,
                $includedData[$entityData->getIndex()],
                $entity,
                $includedEntities->getClass($entity),
                $includedEntities->getId($entity)
            );
            $actionContext->setLastGroup(ApiActionGroup::NORMALIZE_INPUT);
            $actionProcessor->process($actionContext);
            if ($actionContext->hasErrors() && null === $entityData->getRequestData()) {
                // request data for the entity are invalid
                $entityData->setRequestData([]);
            }

            $processingIncludedEntities[] = [$actionProcessor, $actionContext, $entityData];
        }

        // validate and fill all included entities
        /**
         * @var ActionProcessorInterface $actionProcessor
         * @var SingleItemContext&FormContext $actionContext
         * @var IncludedEntityData $entityData
         */
        foreach ($processingIncludedEntities as [$actionProcessor, $actionContext, $entityData]) {
            if (!$actionContext->hasErrors()) {
                $actionContext->setFirstGroup(ApiActionGroup::SECURITY_CHECK);
                $actionContext->setLastGroup(ApiActionGroup::TRANSFORM_DATA);
                $actionProcessor->process($actionContext);
            }
            $this->handleIncludedEntityProcessingResult($context, $actionContext, $entityData);
        }
    }

    private function createIncludedEntityProcessingContext(
        ActionProcessorInterface $actionProcessor,
        FormContext $context,
        array $entityRequestData,
        object $entity,
        string $entityClass,
        mixed $entityIncludeId
    ): FormContext {
        /** @var SingleItemContext&FormContext $actionContext */
        $actionContext = $actionProcessor->createContext();
        $actionContext->setVersion($context->getVersion());
        $actionContext->getRequestType()->set($context->getRequestType());
        $actionContext->setRequestHeaders($context->getRequestHeaders());
        $actionContext->setSharedData($context->getSharedData());
        $actionContext->setEntityMapper($context->getEntityMapper());
        $actionContext->setIncludedEntities($context->getIncludedEntities());

        $actionContext->setClassName($entityClass);
        $actionContext->setId($entityIncludeId);
        $actionContext->setRequestData($entityRequestData);
        $actionContext->setResult($entity);

        $actionContext->skipFormValidation(true);
        $actionContext->setSoftErrorsHandling(true);

        return $actionContext;
    }

    private function handleIncludedEntityProcessingResult(
        FormContext $context,
        FormContext $actionContext,
        IncludedEntityData $entityData
    ): void {
        if ($actionContext->hasErrors()) {
            $requestType = $actionContext->getRequestType();
            $errorCompleter = $this->errorCompleterRegistry->getErrorCompleter($requestType);
            $actionMetadata = $actionContext->getMetadata();
            $errors = $actionContext->getErrors();
            foreach ($errors as $error) {
                $this->completeErrorStatusCode($error);
                $errorCompleter->fixIncludedEntityPath($entityData->getPath(), $error, $requestType, $actionMetadata);
                $context->addError($error);
            }
        } else {
            $entityData->setMetadata($actionContext->getMetadata());
            $entityData->setForm($actionContext->getForm());
        }
    }

    private function completeErrorStatusCode(Error $error): void
    {
        $statusCode = $error->getStatusCode();
        if (null === $error->getStatusCode() && null !== $error->getInnerException()) {
            $statusCode = $this->exceptionTextExtractor->getExceptionStatusCode($error->getInnerException());
        }
        if (null !== $statusCode && $this->isErrorResponseWithoutContent($statusCode)) {
            $statusCode = Response::HTTP_BAD_REQUEST;
        }
        if (null !== $statusCode) {
            $error->setStatusCode($statusCode);
        }
    }
}
