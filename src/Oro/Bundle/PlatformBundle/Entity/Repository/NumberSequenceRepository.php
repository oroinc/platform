<?php

declare(strict_types=1);

namespace Oro\Bundle\PlatformBundle\Entity\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\LockMode;
use Doctrine\DBAL\Types\Types;
use Oro\Bundle\PlatformBundle\Entity\NumberSequence;

/**
 * Entity repository for {@see NumberSequence}.
 */
class NumberSequenceRepository extends ServiceEntityRepository
{
    public function getLockedSequence(
        string $sequenceType,
        string $discriminatorType,
        string $discriminatorValue
    ): ?NumberSequence {
        return $this->createQueryBuilder('NumberSequence')
            ->where('NumberSequence.sequenceType = :sequenceType')
            ->setParameter('sequenceType', $sequenceType, Types::STRING)
            ->andWhere('NumberSequence.discriminatorType = :discriminatorType')
            ->setParameter('discriminatorType', $discriminatorType, Types::STRING)
            ->andWhere('NumberSequence.discriminatorValue = :discriminatorValue')
            ->setParameter('discriminatorValue', $discriminatorValue, Types::STRING)
            ->orderBy('NumberSequence.id', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->setLockMode(LockMode::PESSIMISTIC_WRITE)
            ->getOneOrNullResult();
    }

    public function hasSequences(): bool
    {
        $qb = $this->createQueryBuilder('ns')
            ->select('COUNT(ns.id)');

        return (int)$qb->getQuery()->getSingleScalarResult() > 0;
    }

    /**
     * @return array<array{sequenceType: string, discriminatorType: string}>
     */
    public function findUniqueSequenceTypes(): array
    {
        $qb = $this->createQueryBuilder('ns')
            ->select('DISTINCT ns.sequenceType, ns.discriminatorType');

        return $qb->getQuery()->getArrayResult();
    }

    /**
     * @param array<string, string> $orderBy
     * @return NumberSequence[]
     */
    public function findByTypeAndDiscriminatorOrdered(
        string $sequenceType,
        string $discriminatorType,
        array $orderBy = ['id' => 'DESC']
    ): array {
        return $this->findBy(
            ['sequenceType' => $sequenceType, 'discriminatorType' => $discriminatorType],
            $orderBy
        );
    }
}
