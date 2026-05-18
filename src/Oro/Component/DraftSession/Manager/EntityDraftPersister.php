<?php

declare(strict_types=1);

namespace Oro\Component\DraftSession\Manager;

use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Component\DraftSession\Entity\EntityDraftAwareInterface;
use Oro\Component\DraftSession\Entity\EntityDraftSoftDeleteAwareInterface;
use Oro\Component\DraftSession\Event\EntityDraftPersistAfterEvent;
use Oro\Component\DraftSession\Event\EntityDraftPersistBeforeEvent;
use Oro\Component\DraftSession\Factory\EntityDraftFactoryInterface;
use Oro\Component\DraftSession\Isolator\DoctrineListenersIsolator;
use Oro\Component\DraftSession\Isolator\DraftEntitiesEntityManagerIsolator;
use Oro\Component\DraftSession\Provider\DraftSessionUuidProvider;
use Oro\Component\DraftSession\Provider\EntityDraftRepositoryInterface;
use Oro\Component\DraftSession\Synchronizer\EntityDraftSynchronizerInterface;
use Oro\Component\DraftSession\Util\EntityDraftUtils;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * Persists entity draft state in the context of a draft session.
 *
 * The persister encapsulates draft creation/update logic, listener isolation during persistence,
 * and optional soft-delete flag propagation for draft-aware entities.
 */
class EntityDraftPersister implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    public function __construct(
        private readonly ManagerRegistry $doctrine,
        private readonly EntityDraftRepositoryInterface $entityDraftRepository,
        private readonly DraftSessionUuidProvider $draftSessionUuidProvider,
        private readonly EntityDraftFactoryInterface $entityDraftFactory,
        private readonly EntityDraftSynchronizerInterface $entityDraftSynchronizer,
        private readonly DoctrineListenersIsolator $doctrineListenersIsolator,
        private readonly DraftEntitiesEntityManagerIsolator $entityManagerIsolator,
        private readonly EventDispatcherInterface $eventDispatcher,
    ) {
        $this->logger = new NullLogger();
    }

    /**
     * Saves the given entity state to a draft within the resolved draft session.
     *
     * @param EntityDraftAwareInterface $entity Regular entity or draft entity.
     * @param string|null $draftSessionUuid Draft session UUID; current session UUID is used when null.
     *
     * @return EntityDraftAwareInterface Persisted draft entity.
     */
    public function saveToEntityDraft(
        EntityDraftAwareInterface $entity,
        ?string $draftSessionUuid = null
    ): EntityDraftAwareInterface {
        $draftSessionUuid ??= $this->draftSessionUuidProvider->getDraftSessionUuid();
        if ($draftSessionUuid === null) {
            $this->logger->debug(
                'Entity draft save was skipped because draft session UUID is not available for {entity_class}.',
                [
                    'entity_class' => ClassUtils::getClass($entity),
                    'entity_id' => $entity->getId(),
                ]
            );

            return $entity;
        }

        $isDraft = EntityDraftUtils::isEntityDraft($entity);

        $this->logger->debug(
            'Entity draft save was started for {entity_class}.',
            [
                'entity_class' => ClassUtils::getClass($entity),
                'entity_id' => $entity->getId(),
                'draft_session_uuid' => $draftSessionUuid,
                'is_draft_input' => $isDraft,
            ]
        );

        $entityManager = $this->doctrine->getManagerForClass(ClassUtils::getClass($entity));
        assert($entityManager instanceof EntityManagerInterface);

        $unitOfWork = $entityManager->getUnitOfWork();
        $isScheduledForDelete = $unitOfWork->isScheduledForDelete($entity);

        $this->doctrineListenersIsolator->disableListeners();
        $this->logger->debug('Doctrine listeners were disabled for draft persistence isolation.');

        try {
            if ($isDraft) {
                $draft = $entity;
                $entity = $draft->getDraftSource();
            } else {
                $draft = $this->entityDraftRepository->findEntityDraft($entity, $draftSessionUuid);
                if ($draft !== null) {
                    $this->entityDraftSynchronizer->synchronizeToDraft($entity, $draft);

                    $this->logger->debug(
                        'Existing draft {draft_id} was synchronized from entity {entity_id}.',
                        [
                            'draft_id' => $draft->getId(),
                            'entity_id' => $entity->getId(),
                            'entity_class' => ClassUtils::getClass($entity),
                            'draft_session_uuid' => $draftSessionUuid,
                        ]
                    );
                } else {
                    $draft = $this->entityDraftFactory->createDraft($entity, $draftSessionUuid);

                    $this->logger->debug('New draft was created for entity {entity_class}.', [
                        'entity_class' => ClassUtils::getClass($entity),
                        'entity_id' => $entity->getId(),
                        'draft_id' => $draft->getId(),
                        'draft_session_uuid' => $draftSessionUuid,
                    ]);
                }
            }

            $this->applySoftDeleteFlag($draft, $isScheduledForDelete, $draftSessionUuid);
            $this->persistDraft($entityManager, $draft, $entity, $draftSessionUuid);
        } finally {
            $this->doctrineListenersIsolator->enableListeners();
            $this->logger->debug('Doctrine listeners were re-enabled after draft persistence.');
        }

        return $draft;
    }

    private function applySoftDeleteFlag(
        EntityDraftAwareInterface $draft,
        bool $isScheduledForDelete,
        ?string $draftSessionUuid
    ): void {
        if (!$isScheduledForDelete || !$draft instanceof EntityDraftSoftDeleteAwareInterface) {
            return;
        }

        $draft->setDraftDelete(true);

        $this->logger->debug('Soft-delete flag was applied to draft {draft_id}.', [
            'draft_id' => $draft->getId(),
            'draft_session_uuid' => $draftSessionUuid,
        ]);
    }

    private function persistDraft(
        EntityManagerInterface $entityManager,
        EntityDraftAwareInterface $draft,
        ?EntityDraftAwareInterface $entity,
        ?string $draftSessionUuid
    ): void {
        $this->eventDispatcher->dispatch(new EntityDraftPersistBeforeEvent($draft, $entity));

        $entityManager->persist($draft);

        $this->entityManagerIsolator->flushDraftEntities($entityManager);

        $this->eventDispatcher->dispatch(new EntityDraftPersistAfterEvent($draft, $entity));

        $this->logger->debug('Draft {draft_id} was persisted and flushed.', [
            'draft_id' => $draft->getId(),
            'draft_session_uuid' => $draftSessionUuid,
        ]);
    }
}
