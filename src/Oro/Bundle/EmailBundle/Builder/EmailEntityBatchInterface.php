<?php

namespace Oro\Bundle\EmailBundle\Builder;

use Doctrine\ORM\EntityManager;

interface EmailEntityBatchInterface
{
    /**
     * Tell the given EntityManager to manage this batch
     *
     * @param EntityManager $em
     */
    public function persist(EntityManager $em);

    /**
     * Get the list of all changes made by {@see persist()} method
     * For example new objects can be replaced by existing ones from a database
     *
     * @return array [old, new] The list of changes
     */
    public function getChanges();

    /**
     * Clears batch
     */
    public function clear();
}
