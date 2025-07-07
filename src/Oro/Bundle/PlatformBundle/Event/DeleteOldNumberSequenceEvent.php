<?php

declare(strict_types=1);

namespace Oro\Bundle\PlatformBundle\Event;

use Symfony\Contracts\EventDispatcher\Event;

/**
 * Dispatched to allow listeners to find and delete outdated NumberSequence records based on their own logic,
 * which may vary by sequence type and discriminator type.
 */
class DeleteOldNumberSequenceEvent extends Event
{
    /**
     * @param string $sequenceType The type of sequence, e.g., 'invoice' or 'order'
     * @param string $discriminatorType The subtype or context of the sequence, e.g., 'organization_periodic'
     * or 'regular'
     */
    public function __construct(
        private readonly string $sequenceType,
        private readonly string $discriminatorType
    ) {
    }

    public function getSequenceType(): string
    {
        return $this->sequenceType;
    }

    public function getDiscriminatorType(): string
    {
        return $this->discriminatorType;
    }
}
