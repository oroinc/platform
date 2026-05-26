<?php

declare(strict_types=1);

namespace Oro\Component\DraftSession\Provider;

use Oro\Component\DraftSession\Entity\EntityDraftAwareInterface;

/**
 * Repository contract for entity-class-specific draft lookup implementations.
 */
interface EntityDraftRepositoryInterface
{
    public function supports(string $entityClass): bool;

    public function hasEntityDraft(
        EntityDraftAwareInterface $entityOrDraft,
        string $draftSessionUuid
    ): bool;

    public function findEntityDraft(
        EntityDraftAwareInterface $entityOrDraft,
        string $draftSessionUuid
    ): ?EntityDraftAwareInterface;
}
