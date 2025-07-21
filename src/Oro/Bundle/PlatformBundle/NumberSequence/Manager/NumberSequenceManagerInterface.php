<?php

declare(strict_types=1);

namespace Oro\Bundle\PlatformBundle\NumberSequence\Manager;

/**
 * Interface for managing number sequences.
 */
interface NumberSequenceManagerInterface
{
    /**
     * Safely increments the sequence number and returns it.
     *
     * @return int The next sequence number.
     */
    public function nextNumber(): int;

    /**
     * @param int $number Resets sequence to the specified number.
     */
    public function resetSequence(int $number = 0): void;

    /**
     * @param int $size The number of sequence numbers to reserve.
     *
     * @return array<int> An array of reserved sequence numbers.
     */
    public function reserveSequence(int $size): array;
}
