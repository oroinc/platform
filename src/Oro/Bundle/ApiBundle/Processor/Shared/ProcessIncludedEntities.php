<?php

namespace Oro\Bundle\ApiBundle\Processor\Shared;

use Oro\Bundle\ApiBundle\Collection\IncludedEntityData;
use Oro\Bundle\ApiBundle\Model\Error;
use Oro\Bundle\ApiBundle\Processor\ActionProcessorBagInterface;
use Oro\Bundle\ApiBundle\Processor\FormContext;
use Oro\Bundle\ApiBundle\Processor\SingleItemContext;
use Oro\Bundle\ApiBundle\Request\ApiAction;
use Oro\Bundle\ApiBundle\Request\ApiActionGroup;
use Oro\Bundle\ApiBundle\Request\ErrorCompleterRegistry;
use Oro\Bundle\ApiBundle\Request\ErrorStatusCodesWithoutContentTrait;
use Oro\Bundle\ApiBundle\Request\ExceptionTextExtractorInterface;
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

        $includedData = $context->getIncludedData();
        if (empty($includedData)) {
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
        foreach ($includedEntities as $entity) {
            $entityData = $includedEntities->getData($entity);
            $this->processIncludedEntity(
                $context,
                $includedData[$entityData->getIndex()],
                $entity,
                $includedEntities->getClass($entity),
                $includedEntities->getId($entity),
                $entityData
            );
        }
    }

    private function processIncludedEntity(
        FormContext $context,
        array $entityRequestData,
        object $entity,
        string $entityClass,
        string $entityIncludeId,
        IncludedEntityData $entityData
    ): void {
        $actionProcessor = $this->processorBag->getProcessor(
            $entityData->isExisting() ? ApiAction::UPDATE : ApiAction::CREATE
        );

        /** @var SingleItemContext|FormContext $actionContext */
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
        $actionContext->setLastGroup(ApiActionGroup::TRANSFORM_DATA);
        $actionContext->setSoftErrorsHandling(true);

        $actionProcessor->process($actionContext);

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
