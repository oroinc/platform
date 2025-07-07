<?php

declare(strict_types=1);

namespace Oro\Bundle\PlatformBundle\NumberSequence\Manager;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\PlatformBundle\Entity\NumberSequence;
use Oro\Bundle\PlatformBundle\Entity\Repository\NumberSequenceRepository;

/**
 * Generic implementation of the NumberSequenceManagerInterface that provides sequential number generation
 * with support for transactions, locking, and sequence management.
 *
 * This class manages number sequences that can be used for various purposes like generating
 * invoice numbers, order numbers, or any other sequential identifiers. It provides atomic
 * operations using database transactions and row-level locking to ensure thread-safety.
 *
 * Key features:
 * - Atomic number generation with transaction support
 * - Sequence reservation for batch processing
 * - Sequence reset capability
 * - Support for discriminator-based sequence isolation
 */
class GenericNumberSequenceManager implements NumberSequenceManagerInterface
{
    public function __construct(
        private readonly ManagerRegistry $doctrine,
        private readonly string $sequenceType,
        private readonly string $discriminatorType,
        private readonly string $discriminatorValue
    ) {
    }

    /**
     * Returns the next number in the sequence and increments it.
     *
     * The method runs within a database transaction to ensure atomicity and consistency,
     * using row-level locking to prevent race conditions.
     *
     * @return int The next available number in the sequence.
     *
     * @throws \Exception If any database error occurs during transaction.
     */
    #[\Override]
    public function nextNumber(): int
    {
        $entityManager = $this->getEntityManager();
        $entityManager->beginTransaction();

        try {
            $numberSequence = $this->getOrCreateLockedSequence($entityManager);
            $nextNumber = $numberSequence->getNumber() + 1;
            $numberSequence->setNumber($nextNumber);

            $entityManager->flush();
            $entityManager->commit();

            return $nextNumber;
        } catch (\Exception $exception) {
            $entityManager->rollback();

            throw $exception;
        }
    }

    /**
     * Resets the current sequence number to the specified value.
     *
     * This operation is also transactional and uses locking to ensure consistency.
     *
     * @param int $number The value to reset the sequence to. Must be a non-negative integer.
     *
     * @throws \InvalidArgumentException If the given number is negative.
     * @throws \Exception If any database error occurs during transaction.
     */
    #[\Override]
    public function resetSequence(int $number = 0): void
    {
        if ($number < 0) {
            throw new \InvalidArgumentException('Sequence number must be a positive integer.');
        }

        $entityManager = $this->getEntityManager();
        $entityManager->beginTransaction();

        try {
            $numberSequence = $this->getOrCreateLockedSequence($entityManager);
            $numberSequence->setNumber($number);

            $entityManager->flush();
            $entityManager->commit();
        } catch (\Exception $e) {
            $entityManager->rollback();
            throw $e;
        }
    }

    /**
     * Reserves a range of sequential numbers in a batch.
     *
     * Returns an array of reserved numbers. Useful for pre-generating multiple IDs at once.
     *
     * @param int $size The number of sequence values to reserve. Must be a positive integer.
     *
     * @return int[] The reserved sequence numbers.
     *
     * @throws \InvalidArgumentException If the size is not positive.
     * @throws \Exception If any database error occurs during transaction.
     */
    #[\Override]
    public function reserveSequence(int $size): array
    {
        if ($size <= 0) {
            throw new \InvalidArgumentException('Size must be a positive integer.');
        }

        $entityManager = $this->getEntityManager();
        $entityManager->beginTransaction();

        try {
            $numberSequence = $this->getOrCreateLockedSequence($entityManager);

            $start = $numberSequence->getNumber() + 1;
            $end = $start + $size - 1;
            $numberSequence->setNumber($end);

            $entityManager->flush();
            $entityManager->commit();

            return range($start, $end);
        } catch (\Exception $e) {
            $entityManager->rollback();
            throw $e;
        }
    }

    /**
     * Gets the EntityManager for the NumberSequence entity.
     *
     * @return EntityManagerInterface The entity manager instance.
     */
    private function getEntityManager(): EntityManagerInterface
    {
        return $this->doctrine->getManagerForClass(NumberSequence::class);
    }

    /**
     * Retrieves an existing number sequence with row-level locking or creates a new one if not found.
     *
     * The method uses a custom repository to fetch the sequence with a database lock.
     *
     * @param EntityManagerInterface $entityManager The active entity manager.
     *
     * @return NumberSequence The locked or newly created sequence entity.
     */
    private function getOrCreateLockedSequence(EntityManagerInterface $entityManager): NumberSequence
    {
        /** @var NumberSequenceRepository $repository */
        $repository = $entityManager->getRepository(NumberSequence::class);

        $sequence = $repository->getLockedSequence(
            $this->sequenceType,
            $this->discriminatorType,
            $this->discriminatorValue
        );

        if (!$sequence) {
            $sequence = (new NumberSequence())
                ->setSequenceType($this->sequenceType)
                ->setDiscriminatorType($this->discriminatorType)
                ->setDiscriminatorValue($this->discriminatorValue)
                ->setNumber(0);

            $entityManager->persist($sequence);
        }

        return $sequence;
    }
}
