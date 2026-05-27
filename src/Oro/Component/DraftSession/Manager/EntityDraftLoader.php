<?php

declare(strict_types=1);

namespace Oro\Component\DraftSession\Manager;

use Doctrine\Common\Util\ClassUtils;
use Oro\Component\DraftSession\Entity\EntityDraftAwareInterface;
use Oro\Component\DraftSession\Provider\DraftSessionUuidProvider;
use Oro\Component\DraftSession\Provider\EntityDraftRepositoryInterface;
use Oro\Component\DraftSession\Synchronizer\EntityDraftSynchronizerInterface;
use Oro\Component\DraftSession\Util\EntityDraftUtils;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;

/**
 * Loads a regular entity state from a draft in the context of a draft session.
 */
class EntityDraftLoader implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    public function __construct(
        private readonly EntityDraftRepositoryInterface $entityDraftRepository,
        private readonly DraftSessionUuidProvider $draftSessionUuidProvider,
        private readonly EntityDraftSynchronizerInterface $entityDraftSynchronizer
    ) {
        $this->logger = new NullLogger();
    }

    /**
     * Loads and synchronizes entity data from draft storage.
     *
     * Behavior summary:
     * - If input is a draft of an existing entity, synchronizes draft to that entity.
     * - If input is a draft without source, instantiates a new entity and synchronizes draft into it.
     * - If input is a regular entity, loads its draft in the session and synchronizes it when present.
     *
     * @param EntityDraftAwareInterface $entity Regular entity or its draft.
     * @param string|null $draftSessionUuid Draft session UUID; current session UUID is used when null.
     *
     * @return EntityDraftAwareInterface Regular entity with draft state applied when applicable.
     */
    public function loadFromEntityDraft(
        EntityDraftAwareInterface $entity,
        ?string $draftSessionUuid = null
    ): EntityDraftAwareInterface {
        $draftSessionUuid ??= $this->draftSessionUuidProvider->getDraftSessionUuid();
        $isDraft = EntityDraftUtils::isEntityDraft($entity);

        $this->logger->debug(
            'Entity draft load was started for {entity_class}.',
            [
                'entity_class' => ClassUtils::getClass($entity),
                'entity_id' => $entity->getId(),
                'draft_session_uuid' => $draftSessionUuid,
                'is_draft_input' => $isDraft,
            ]
        );


        if ($isDraft) {
            return $this->loadFromDraftInput($entity, $draftSessionUuid);
        }

        return $this->loadFromRegularEntity($entity, $draftSessionUuid);
    }

    private function loadFromDraftInput(
        EntityDraftAwareInterface $draft,
        ?string $draftSessionUuid
    ): EntityDraftAwareInterface {
        $draftSessionUuid ??= $draft->getDraftSessionUuid();

        $this->logger->debug('Draft input was detected for {entity_class}.', [
            'entity_class' => ClassUtils::getClass($draft),
            'draft_id' => $draft->getId(),
            'draft_session_uuid' => $draftSessionUuid,
        ]);

        $entity = $draft->getDraftSource();
        if ($entity === null) {
            return $this->createAndSyncEntityFromDraft($draft, $draftSessionUuid);
        }

        if ($entity->getId() === null) {
            $this->logger->debug(
                'Synchronization from draft {draft_id} was skipped for new source entity.',
                [
                    'draft_id' => $draft->getId(),
                    'source_entity_id' => $entity->getId(),
                    'draft_session_uuid' => $draftSessionUuid,
                ]
            );

            return $entity;
        }

        $this->entityDraftSynchronizer->synchronizeFromDraft($draft, $entity);

        $this->logger->debug(
            'Existing entity {entity_id} was synchronized from draft {draft_id}.',
            [
                'entity_id' => $entity->getId(),
                'draft_id' => $draft->getId(),
                'draft_session_uuid' => $draftSessionUuid,
            ]
        );

        return $entity;
    }

    private function loadFromRegularEntity(
        EntityDraftAwareInterface $entity,
        ?string $draftSessionUuid
    ): EntityDraftAwareInterface {
        $this->logger->debug(
            'Draft lookup was started for regular entity {entity_class}.',
            [
                'entity_class' => ClassUtils::getClass($entity),
                'entity_id' => $entity->getId(),
                'draft_session_uuid' => $draftSessionUuid,
            ]
        );

        if ($draftSessionUuid === null) {
            $this->logger->debug(
                'Draft session UUID is null for regular entity {entity_class}, draft lookup is skipped.',
                [
                    'entity_class' => ClassUtils::getClass($entity),
                    'entity_id' => $entity->getId(),
                ]
            );

            return $entity;
        }

        $draft = $this->entityDraftRepository->findEntityDraft($entity, $draftSessionUuid);
        if ($draft === null) {
            $this->logger->debug(
                'Draft was not found for regular entity {entity_class}.',
                [
                    'entity_class' => ClassUtils::getClass($entity),
                    'entity_id' => $entity->getId(),
                    'draft_session_uuid' => $draftSessionUuid,
                ]
            );

            return $entity;
        }

        $this->entityDraftSynchronizer->synchronizeFromDraft($draft, $entity);

        $this->logger->debug(
            'Regular entity {entity_id} was synchronized from draft {draft_id}.',
            [
                'entity_id' => $entity->getId(),
                'draft_id' => $draft->getId(),
                'draft_session_uuid' => $draftSessionUuid,
            ]
        );

        return $entity;
    }

    private function createAndSyncEntityFromDraft(
        EntityDraftAwareInterface $draft,
        ?string $draftSessionUuid
    ): EntityDraftAwareInterface {
        $entity = new (ClassUtils::getClass($draft));
        $this->entityDraftSynchronizer->synchronizeFromDraft($draft, $entity);

        $this->logger->debug(
            'New entity instance was created and synchronized from draft {draft_id}.',
            [
                'draft_id' => $draft->getId(),
                'entity_class' => ClassUtils::getClass($draft),
                'draft_session_uuid' => $draftSessionUuid,
            ]
        );

        return $entity;
    }
}
