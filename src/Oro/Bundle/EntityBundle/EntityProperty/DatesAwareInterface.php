<?php

namespace Oro\Bundle\EntityBundle\EntityProperty;

/**
 * Defines the contract for entities that track both creation and update timestamps.
 *
 * Entities implementing this interface maintain both `createdAt` and `updatedAt` properties
 * to track when the entity was created and last modified. This is typically managed
 * automatically by the ORM or event listeners.
 */
interface DatesAwareInterface extends CreatedAtAwareInterface, UpdatedAtAwareInterface
{
}
