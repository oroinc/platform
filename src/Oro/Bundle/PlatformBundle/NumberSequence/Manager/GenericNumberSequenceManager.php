<?php

declare(strict_types=1);

namespace Oro\Bundle\PlatformBundle\NumberSequence\Manager;

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
        /** @var NumberSequenceRepository $numberSequenceRepository */
        $numberSequenceRepository = $this->doctrine->getRepository(NumberSequence::class);
        $numberSequence = $numberSequenceRepository
            ->incrementSequence($this->sequenceType, $this->discriminatorType, $this->discriminatorValue);

        return $numberSequence->getNumber();
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

        /** @var NumberSequenceRepository $numberSequenceRepository */
        $numberSequenceRepository = $this->doctrine->getRepository(NumberSequence::class);
        $numberSequenceRepository
            ->resetSequence($this->sequenceType, $this->discriminatorType, $this->discriminatorValue, $number);
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

        /** @var NumberSequenceRepository $numberSequenceRepository */
        $numberSequenceRepository = $this->doctrine->getRepository(NumberSequence::class);
        $numberSequence = $numberSequenceRepository
            ->incrementSequence($this->sequenceType, $this->discriminatorType, $this->discriminatorValue, $size);

        $start = $numberSequence->getNumber() - $size + 1;
        $end = $numberSequence->getNumber();

        return range($start, $end);
    }
}
