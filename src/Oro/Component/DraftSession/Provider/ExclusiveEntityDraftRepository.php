<?php

declare(strict_types=1);

namespace Oro\Component\DraftSession\Provider;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Component\DraftSession\Entity\EntityDraftAwareInterface;

/**
 * Draft repository that finds a draft by a draft session UUID exclusively.
 */
class ExclusiveEntityDraftRepository implements EntityDraftRepositoryInterface
{
    /**
     * @param ManagerRegistry $doctrine
     * @param string $entityClass The fully qualified entity class name that this repository supports.
     */
    public function __construct(
        private readonly ManagerRegistry $doctrine,
        private readonly string $entityClass
    ) {
    }

    #[\Override]
    public function supports(string $entityClass): bool
    {
        return is_a($this->entityClass, $entityClass, true);
    }

    #[\Override]
    public function hasEntityDraft(
        EntityDraftAwareInterface $entityOrDraft,
        string $draftSessionUuid
    ): bool {
        /** @var QueryBuilder $qb */
        $qb = $this->doctrine
            ->getRepository($this->entityClass)
            ->createQueryBuilder('entity')
            ->select('entity.id');

        assert($qb instanceof QueryBuilder);

        $qb
            ->where($qb->expr()->eq('entity.draftSessionUuid', ':draftSessionUuid'))
            ->setParameter('draftSessionUuid', $draftSessionUuid, Types::GUID);

        return $qb->getQuery()->getOneOrNullResult(AbstractQuery::HYDRATE_SCALAR) !== null;
    }

    #[\Override]
    public function findEntityDraft(
        EntityDraftAwareInterface $entityOrDraft,
        string $draftSessionUuid
    ): ?EntityDraftAwareInterface {
        $qb = $this->doctrine
            ->getRepository($this->entityClass)
            ->createQueryBuilder('entity');

        assert($qb instanceof QueryBuilder);

        $qb
            ->where($qb->expr()->eq('entity.draftSessionUuid', ':draftSessionUuid'))
            ->setParameter('draftSessionUuid', $draftSessionUuid, Types::GUID);

        return $qb->getQuery()->getOneOrNullResult();
    }
}
