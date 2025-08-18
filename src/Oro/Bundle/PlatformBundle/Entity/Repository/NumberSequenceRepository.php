<?php

declare(strict_types=1);

namespace Oro\Bundle\PlatformBundle\Entity\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\Types\Types;
use Oro\Bundle\PlatformBundle\Entity\NumberSequence;

/**
 * Entity repository for {@see NumberSequence}.
 */
class NumberSequenceRepository extends ServiceEntityRepository
{
    /**
     * Upserts and increments the sequence number for the given sequence type and discriminator.
     *
     * @param string $sequenceType The type of sequence, e.g., 'invoice' or 'order'.
     * @param string $discriminatorType The subtype or context of the sequence, e.g., 'organization_periodic'
     *  or 'regular'.
     * @param string $discriminatorValue
     * @param int $number
     *
     * @return NumberSequence
     */
    public function incrementSequence(
        string $sequenceType,
        string $discriminatorType,
        string $discriminatorValue,
        int $number = 1
    ): NumberSequence {
        $updateClause = 'number = oro_number_sequence.number + EXCLUDED.number';

        return $this->upsertSequence(
            $sequenceType,
            $discriminatorType,
            $discriminatorValue,
            $number,
            $updateClause
        );
    }

    /**
     * Resets the sequence number to the specified value for the given sequence type and discriminator.
     *
     * @param string $sequenceType The type of sequence, e.g., 'invoice' or 'order'.
     * @param string $discriminatorType The subtype or context of the sequence, e.g., 'organization_periodic'
     *  or 'regular'.
     * @param string $discriminatorValue
     * @param int $number
     * @return NumberSequence
     */
    public function resetSequence(
        string $sequenceType,
        string $discriminatorType,
        string $discriminatorValue,
        int $number = 0
    ): NumberSequence {
        $updateClause = 'number = EXCLUDED.number';

        return $this->upsertSequence(
            $sequenceType,
            $discriminatorType,
            $discriminatorValue,
            $number,
            $updateClause
        );
    }

    private function upsertSequence(
        string $sequenceType,
        string $discriminatorType,
        string $discriminatorValue,
        int $number,
        string $updateClause
    ): NumberSequence {
        $connection = $this->getEntityManager()->getConnection();
        $now = new \DateTime('now', new \DateTimeZone('UTC'));

        $sql = 'INSERT INTO oro_number_sequence 
                (sequence_type, discriminator_type, discriminator_value, number, created_at, updated_at) 
                VALUES (?, ?, ?, ?, ?, ?)
                ON CONFLICT (sequence_type, discriminator_type, discriminator_value) 
                DO UPDATE SET 
                    ' . $updateClause . ',
                    updated_at = EXCLUDED.updated_at
                RETURNING id, number, created_at, updated_at';

        $result = $connection->executeQuery(
            $sql,
            [
                $sequenceType,
                $discriminatorType,
                $discriminatorValue,
                $number,
                $now,
                $now,
            ],
            [
                Types::STRING,
                Types::STRING,
                Types::STRING,
                Types::INTEGER,
                Types::DATETIME_MUTABLE,
                Types::DATETIME_MUTABLE,
            ]
        );

        $row = $result->fetchAssociative();

        $numberSequence = new NumberSequence();
        $numberSequence->setId((int)$row['id']);
        $numberSequence->setSequenceType($sequenceType);
        $numberSequence->setDiscriminatorType($discriminatorType);
        $numberSequence->setDiscriminatorValue($discriminatorValue);
        $numberSequence->setNumber($row['number']);

        $createdAt = $connection->convertToPHPValue($row['created_at'], Types::DATETIME_MUTABLE);
        $numberSequence->setCreatedAt($createdAt);
        $updatedAt = $connection->convertToPHPValue($row['updated_at'], Types::DATETIME_MUTABLE);
        $numberSequence->setUpdatedAt($updatedAt);

        $this->getEntityManager()->merge($numberSequence);

        return $numberSequence;
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
