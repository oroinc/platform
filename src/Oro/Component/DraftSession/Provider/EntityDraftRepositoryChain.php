<?php

declare(strict_types=1);

namespace Oro\Component\DraftSession\Provider;

use Doctrine\Common\Util\ClassUtils;
use Oro\Component\DraftSession\Entity\EntityDraftAwareInterface;

/**
 * Delegates draft lookup to a repository that supports the given entity class.
 */
class EntityDraftRepositoryChain implements EntityDraftRepositoryInterface
{
    /**
     * @param iterable<EntityDraftRepositoryInterface> $entityDraftRepositories
     */
    public function __construct(
        private readonly iterable $entityDraftRepositories,
    ) {
    }

    #[\Override]
    public function supports(string $entityClass): bool
    {
        return $this->resolveEntityDraftRepository($entityClass) !== null;
    }

    #[\Override]
    public function hasEntityDraft(EntityDraftAwareInterface $entityOrDraft, string $draftSessionUuid): bool
    {
        $entityDraftRepository = $this->resolveEntityDraftRepository(ClassUtils::getClass($entityOrDraft));
        if ($entityDraftRepository === null) {
            return false;
        }

        return $entityDraftRepository->hasEntityDraft($entityOrDraft, $draftSessionUuid);
    }

    #[\Override]
    public function findEntityDraft(
        EntityDraftAwareInterface $entityOrDraft,
        string $draftSessionUuid
    ): ?EntityDraftAwareInterface {
        $entityDraftRepository = $this->resolveEntityDraftRepository(ClassUtils::getClass($entityOrDraft));
        if ($entityDraftRepository === null) {
            return null;
        }

        return $entityDraftRepository->findEntityDraft($entityOrDraft, $draftSessionUuid);
    }

    private function resolveEntityDraftRepository(string $entityClass): ?EntityDraftRepositoryInterface
    {
        foreach ($this->entityDraftRepositories as $entityDraftRepository) {
            if ($entityDraftRepository->supports($entityClass)) {
                return $entityDraftRepository;
            }
        }

        return null;
    }
}
