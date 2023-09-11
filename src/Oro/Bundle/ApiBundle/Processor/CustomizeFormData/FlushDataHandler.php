<?php

namespace Oro\Bundle\ApiBundle\Processor\CustomizeFormData;

use Doctrine\Common\Util\ClassUtils;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\UnitOfWork;
use Oro\Bundle\ApiBundle\Collection\AdditionalEntityCollection;
use Oro\Bundle\ApiBundle\Processor\FormContext;
use Oro\Bundle\ApiBundle\Processor\Shared\CollectFormErrors;
use Oro\Bundle\ApiBundle\Processor\Subresource\ChangeRelationshipContext;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Form\FormInterface;

/**
 * The handler to flush all ORM entities that are changed by API to the database.
 * This handler dispatches flush data related events for a primary entity and all included entities.
 * The events for included entities are dispatched before the events for the primary entity.
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
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

        $entityContexts = $context->getEntityContexts();
        foreach ($entityContexts as $entityContext) {
            $this->persistAdditionalEntities($entityManager, $entityContext->getAdditionalEntityCollection());
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
        $this->dispatchForIncludedEntities($eventName, $entityContext);

        $form = $entityContext->getForm();
        if (null !== $form) {
            $eventContext = $this->getApiEventContext($form);
            if (null === $eventContext) {
                $this->customizeFormDataEventDispatcher->dispatch($eventName, $form);
            } elseif ($entityContext->hasResult()) {
                $eventContext->setData($entityContext->getResult());
                $this->customizeFormDataEventDispatcher->dispatch($eventName, $form);
                $this->updateResult($entityContext, $eventContext, true);
            } else {
                $eventContext->setData(null);
                $this->customizeFormDataEventDispatcher->dispatch($eventName, $form);
                $this->updateResult($entityContext, $eventContext);
            }
        }
    }

    private function dispatchForIncludedEntities(string $eventName, FormContext $entityContext): void
    {
        $includedEntities = $entityContext->getIncludedEntities();
        if (null === $includedEntities) {
            return;
        }

        foreach ($includedEntities as $entity) {
            $entityData = $includedEntities->getData($entity);
            if (null === $entityData) {
                continue;
            }
            $entityForm = $entityData->getForm();
            if (null === $entityForm) {
                continue;
            }
            $this->customizeFormDataEventDispatcher->dispatch($eventName, $entityForm);
            $eventContext = $this->getApiEventContext($entityForm);
            if (null === $eventContext) {
                continue;
            }
            $eventEntity = $eventContext->getData();
            if ($this->shouldEntityBeReplaced($entity, $eventEntity, $eventContext)) {
                $entityClass = $includedEntities->getClass($entity);
                $entityId = $includedEntities->getId($entity);
                $includedEntities->remove($entityClass, $entityId);
                $includedEntities->add($eventEntity, $entityClass, $entityId, $entityData);
                $entityContext->addAdditionalEntity($eventEntity);
            }
            $eventAdditionalEntityCollection = $eventContext->getAdditionalEntityCollection();
            foreach ($eventAdditionalEntityCollection->getEntities() as $eventAdditionalEntity) {
                $entityContext->getAdditionalEntityCollection()->add(
                    $eventAdditionalEntity,
                    $eventAdditionalEntityCollection->shouldEntityBeRemoved($eventAdditionalEntity)
                );
            }
        }
    }

    private function updateResult(
        FormContext $entityContext,
        CustomizeFormDataContext $eventContext,
        bool $ignoreNull = false
    ): void {
        $eventEntity = $eventContext->getData();
        if (null === $eventEntity && !$ignoreNull) {
            return;
        }

        $entityContext->setResult($eventEntity);
        $includedEntities = $entityContext->getIncludedEntities();
        if (null !== $includedEntities
            && $this->shouldEntityBeReplaced($includedEntities->getPrimaryEntity(), $eventEntity, $eventContext)
        ) {
            $includedEntities->setPrimaryEntity($eventEntity, $includedEntities->getPrimaryEntityMetadata());
        }
    }

    private function shouldEntityBeReplaced(
        object $entity,
        ?object $eventEntity,
        CustomizeFormDataContext $eventContext
    ): bool {
        return
            null !== $eventEntity
            && $eventEntity !== $entity
            && !is_a($eventEntity, $eventContext->getClassName());
    }

    private function getApiEventContext(FormInterface $form): ?CustomizeFormDataContext
    {
        return $form->getConfig()->getAttribute(CustomizeFormDataHandler::API_EVENT_CONTEXT);
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

    private function persistAdditionalEntities(
        EntityManagerInterface $entityManager,
        AdditionalEntityCollection $additionalEntities
    ): void {
        foreach ($additionalEntities->getEntities() as $entity) {
            if (!$this->isManageableEntity($entityManager, $entity)) {
                continue;
            }

            if ($additionalEntities->shouldEntityBeRemoved($entity)) {
                $this->removeEntity($entityManager, $entity);
            } else {
                $this->persistEntity($entityManager, $entity);
            }
        }
    }

    private function persistEntity(EntityManagerInterface $entityManager, object $entity): void
    {
        if (UnitOfWork::STATE_NEW !== $entityManager->getUnitOfWork()->getEntityState($entity)) {
            return;
        }

        $entityManager->persist($entity);
    }

    private function removeEntity(EntityManagerInterface $entityManager, object $entity): void
    {
        if (UnitOfWork::STATE_MANAGED !== $entityManager->getUnitOfWork()->getEntityState($entity)) {
            return;
        }

        $entityManager->remove($entity);
    }

    public function isManageableEntity(EntityManagerInterface $entityManager, object $entity): bool
    {
        return !$entityManager->getMetadataFactory()->isTransient(ClassUtils::getClass($entity));
    }
}
