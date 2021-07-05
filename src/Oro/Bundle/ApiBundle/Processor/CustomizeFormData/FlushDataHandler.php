<?php

namespace Oro\Bundle\ApiBundle\Processor\CustomizeFormData;

use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\ApiBundle\Processor\FormContext;
use Oro\Bundle\ApiBundle\Processor\Shared\CollectFormErrors;
use Oro\Bundle\ApiBundle\Processor\Subresource\ChangeRelationshipContext;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Psr\Log\LoggerInterface;

/**
 * The handler to flush all ORM entities that are changed by API to the database.
 * This handler dispatches flush data related events for a primary entity and all included entities.
 * The events for included entities are dispatched before the events for the primary entity.
 */
class FlushDataHandler implements FlushDataHandlerInterface
{
    private CustomizeFormDataEventDispatcher $customizeFormDataEventDispatcher;
    private ProcessorInterface $formErrorsCollector;
    private ProcessorInterface $formErrorsCollectorForSubresource;
    private LoggerInterface $logger;

    public function __construct(
        CustomizeFormDataEventDispatcher $customizeFormDataEventDispatcher,
        ProcessorInterface $formErrorsCollector,
        ProcessorInterface $formErrorsCollectorForSubresource,
        LoggerInterface $logger
    ) {
        $this->customizeFormDataEventDispatcher = $customizeFormDataEventDispatcher;
        $this->formErrorsCollector = $formErrorsCollector;
        $this->formErrorsCollectorForSubresource = $formErrorsCollectorForSubresource;
        $this->logger = $logger;
    }

    /**
     * {@inheritDoc}
     */
    public function flushData(EntityManagerInterface $entityManager, FlushDataHandlerContext $context): void
    {
        $successfullyFlushed = false;
        $connection = $entityManager->getConnection();
        $connection->beginTransaction();
        try {
            if ($this->doFlush($context, $entityManager, $connection)) {
                $connection->commit();
                $successfullyFlushed = true;
            }
        } catch (\Throwable $e) {
            $this->safeRollbackTransaction($connection, $context);

            throw $e;
        }

        if ($successfullyFlushed) {
            $this->dispatchEvent(CustomizeFormDataContext::EVENT_POST_SAVE_DATA, $context);
        }
    }

    private function doFlush(
        FlushDataHandlerContext $context,
        EntityManagerInterface $entityManager,
        Connection $connection
    ): bool {
        if (!$this->dispatchFlushEvent(CustomizeFormDataContext::EVENT_PRE_FLUSH_DATA, $context, $connection)) {
            return false;
        }

        $entityManager->flush();

        if (!$this->dispatchFlushEvent(CustomizeFormDataContext::EVENT_POST_FLUSH_DATA, $context, $connection)) {
            return false;
        }

        return true;
    }

    /**
     * Rolls back the database transaction with ignoring any exceptions that may occur during this operation.
     * It is done to prevent overriding of an exception that is the cause of the rollback.
     */
    private function safeRollbackTransaction(Connection $connection, FlushDataHandlerContext $context): void
    {
        try {
            $connection->rollBack();
        } catch (\Throwable $e) {
            $entityClasses = [];
            $entityContexts = $context->getEntityContexts();
            foreach ($entityContexts as $entityContext) {
                $entityClass = $entityContext->getClassName();
                if (!isset($entityClasses[$entityClass])) {
                    $entityClasses[$entityClass] = $entityClass;
                }
            }
            $this->logger->error(
                'The database rollback operation failed in API flush data handler.',
                ['exception' => $e, 'entityClasses' => implode(', ', array_values($entityClasses))]
            );
        }
    }

    /**
     * Dispatches the given flush data related event for all primary entities
     * and all included entities from the given flush data context.
     */
    private function dispatchFlushEvent(
        string $eventName,
        FlushDataHandlerContext $context,
        Connection $connection
    ): bool {
        $this->dispatchEvent($eventName, $context);

        if ($this->hasErrors($context)) {
            $this->safeRollbackTransaction($connection, $context);

            return false;
        }

        return true;
    }

    /**
     * Dispatches the given event for all primary entities
     * and all included entities from the given flush data context.
     */
    private function dispatchEvent(string $eventName, FlushDataHandlerContext $context): void
    {
        $entityContexts = $context->getEntityContexts();
        foreach ($entityContexts as $entityContext) {
            $this->dispatch($eventName, $entityContext);
            $this->collectFormErrors($entityContext);
        }
    }

    /**
     * Dispatches the given flush data related event for a primary entity
     * and all included entities of the given API context.
     */
    private function dispatch(string $eventName, FormContext $entityContext): void
    {
        $includedEntities = $entityContext->getIncludedEntities();
        if (null !== $includedEntities) {
            foreach ($includedEntities as $includedEntity) {
                $includedEntityForm = $includedEntities->getData($includedEntity)->getForm();
                if (null !== $includedEntityForm) {
                    $this->customizeFormDataEventDispatcher->dispatch($eventName, $includedEntityForm);
                }
            }
        }
        $form = $entityContext->getForm();
        if (null !== $form) {
            $this->customizeFormDataEventDispatcher->dispatch($eventName, $form);
        }
    }

    /**
     * Collects errors added to forms during handling of data related events for primary and included entities
     * and adds them into the context.
     */
    private function collectFormErrors(FormContext $entityContext): void
    {
        $entityContext->clearProcessed(CollectFormErrors::OPERATION_NAME);
        if ($entityContext instanceof ChangeRelationshipContext) {
            $this->formErrorsCollectorForSubresource->process($entityContext);
        } else {
            $this->formErrorsCollector->process($entityContext);
        }
    }

    /**
     * Checks if any primary entity or any included entity has any errors.
     */
    private function hasErrors(FlushDataHandlerContext $context): bool
    {
        $entityContexts = $context->getEntityContexts();
        foreach ($entityContexts as $entityContext) {
            if ($entityContext->hasErrors()) {
                return true;
            }
        }

        return false;
    }
}
