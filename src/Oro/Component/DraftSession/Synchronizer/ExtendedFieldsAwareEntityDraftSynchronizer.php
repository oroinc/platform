<?php

declare(strict_types=1);

namespace Oro\Component\DraftSession\Synchronizer;

use Oro\Component\DraftSession\Entity\EntityDraftAwareInterface;
use Oro\Component\DraftSession\ExtendedFields\EntityDraftExtendedFieldsProvider;
use Oro\Component\DraftSession\ExtendedFields\EntityDraftExtendedFieldSynchronizer;

/**
 * Synchronizes extended (custom) entity fields between source and target entities during draft sync.
 *
 * This is a reusable synchronizer that can be registered for any entity class
 * by configuring the supported entity class via the constructor.
 */
class ExtendedFieldsAwareEntityDraftSynchronizer implements EntityDraftSynchronizerInterface
{
    /**
     * @param class-string<EntityDraftAwareInterface> $entityClass
     */
    public function __construct(
        private readonly EntityDraftExtendedFieldsProvider $extendedFieldsProvider,
        private readonly EntityDraftExtendedFieldSynchronizer $extendedFieldSynchronizer,
        private readonly string $entityClass,
    ) {
    }

    #[\Override]
    public function supports(string $entityClass): bool
    {
        return $entityClass === $this->entityClass;
    }

    #[\Override]
    public function synchronizeFromDraft(EntityDraftAwareInterface $draft, EntityDraftAwareInterface $entity): void
    {
        $this->synchronizeExtendedFields($draft, $entity);
    }

    #[\Override]
    public function synchronizeToDraft(EntityDraftAwareInterface $entity, EntityDraftAwareInterface $draft): void
    {
        $this->synchronizeExtendedFields($entity, $draft);
    }

    private function synchronizeExtendedFields(object $source, object $target): void
    {
        $applicableExtendedFields = $this->extendedFieldsProvider->getApplicableExtendedFields($this->entityClass);
        foreach ($applicableExtendedFields as $fieldName => $fieldType) {
            $this->extendedFieldSynchronizer->synchronize($source, $target, $fieldName, $fieldType);
        }
    }
}
