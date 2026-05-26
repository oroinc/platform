<?php

declare(strict_types=1);

namespace Oro\Component\DraftSession\Manager;

use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Component\DraftSession\Entity\EntityDraftAwareInterface;
use Oro\Component\DraftSession\Event\EntityDraftDeleteBeforeEvent;
use Oro\Component\DraftSession\Isolator\DoctrineListenersIsolator;
use Oro\Component\DraftSession\Isolator\DraftEntitiesEntityManagerIsolator;
use Oro\Component\DraftSession\Provider\DraftSessionUuidProvider;
use Oro\Component\DraftSession\Provider\EntityDraftRepositoryInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * Removes entity drafts in the context of a draft session.
 *
 * Deletion is performed with Doctrine listeners isolated to avoid side effects during draft cleanup.
 */
class EntityDraftRemover implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    public function __construct(
        private readonly ManagerRegistry $doctrine,
        private readonly EntityDraftRepositoryInterface $entityDraftRepository,
        private readonly DraftSessionUuidProvider $draftSessionUuidProvider,
        private readonly DoctrineListenersIsolator $doctrineListenersIsolator,
        private readonly DraftEntitiesEntityManagerIsolator $entityManagerIsolator,
        private readonly EventDispatcherInterface $eventDispatcher,
    ) {
        $this->logger = new NullLogger();
    }

    /**
     * Deletes a draft associated with the given entity in the resolved draft session.
     *
     * If no draft exists, the method is a no-op.
     *
     * @param EntityDraftAwareInterface $entity Regular entity or draft entity used for draft lookup.
     * @param string|null $draftSessionUuid Draft session UUID; current session UUID is used when null.
     */
    public function deleteEntityDraft(
        EntityDraftAwareInterface $entity,
        ?string $draftSessionUuid = null
    ): void {
        $draftSessionUuid ??= $this->draftSessionUuidProvider->getDraftSessionUuid();
        if ($draftSessionUuid === null) {
            return;
        }

        $this->logger->debug('Entity draft deletion was started for {entity_class}.', [
            'entity_class' => ClassUtils::getClass($entity),
            'entity_id' => $entity->getId(),
            'draft_session_uuid' => $draftSessionUuid,
        ]);

        $entityManager = $this->doctrine->getManagerForClass(ClassUtils::getClass($entity));
        assert($entityManager instanceof EntityManagerInterface);

        $this->doctrineListenersIsolator->disableListeners();
        $this->logger->debug('Doctrine listeners were disabled for draft deletion isolation.');

        try {
            $draft = $this->entityDraftRepository->findEntityDraft($entity, $draftSessionUuid);
            if ($draft !== null) {
                $this->logger->debug('Draft {draft_id} was found and removal was started.', [
                    'draft_id' => $draft->getId(),
                    'entity_class' => ClassUtils::getClass($entity),
                    'entity_id' => $entity->getId(),
                    'draft_session_uuid' => $draftSessionUuid,
                ]);

                $this->eventDispatcher->dispatch(new EntityDraftDeleteBeforeEvent($draft));

                $entityManager->remove($draft);

                $this->entityManagerIsolator->flushDraftEntities($entityManager);

                $this->logger->debug('Draft {draft_id} was removed and flushed.', [
                    'draft_id' => $draft->getId(),
                    'draft_session_uuid' => $draftSessionUuid,
                ]);
            } else {
                $this->logger->debug('Draft was not found for entity {entity_class}.', [
                    'entity_class' => ClassUtils::getClass($entity),
                    'entity_id' => $entity->getId(),
                    'draft_session_uuid' => $draftSessionUuid,
                ]);
            }
        } finally {
            $this->doctrineListenersIsolator->enableListeners();
            $this->logger->debug('Doctrine listeners were re-enabled after draft deletion.');
        }
    }
}
