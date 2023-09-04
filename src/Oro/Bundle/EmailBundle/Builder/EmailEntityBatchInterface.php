<?php

namespace Oro\Bundle\EmailBundle\Builder;

use Doctrine\ORM\EntityManagerInterface;

/**
 * Represents the butch processor for Email entity.
 */
interface EmailEntityBatchInterface
{
    /**
     * Tells the given entity manager to manage entities involved into this batch
     * and returns the list of all persisted entities.
     */
    public function persist(EntityManagerInterface $em, bool $dryRun = false): array;

    /**
     * Gets the list of all changes made by {@see persist()} method
     * For example new objects can be replaced by existing ones from a database.
     *
     * @return array [old, new] The list of changes
     */
    public function getChanges();

    /**
     * Clears the batch.
     */
    public function clear();
}
