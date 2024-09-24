<?php

namespace Oro\Component\Testing\Unit\ORM\Mocks;

use Doctrine\ORM\UnitOfWork;

/**
 * Special UnitOfWork mock used for testing purposes.
 */
class UnitOfWorkMock extends UnitOfWork
{
    protected $insertions = [];
    protected $deletions = [];
    protected $updates = [];
    protected $changeSets = [];
    protected $collectionUpdates = [];
    protected $collectionDeletions = [];

    public function __construct()
    {
    }

    /**
     * @param object $entity
     * @param array $changeSet
     *
     * @return $this
     */
    public function addInsertion($entity, array $changeSet = [])
    {
        $this->insertions[spl_object_hash($entity)] = $entity;
        $this->addChangeSet($entity, $changeSet);

        return $this;
    }

    /**
     * @param object $entity
     * @param array $changeSet
     *
     * @return $this
     */
    public function addUpdate($entity, array $changeSet = [])
    {
        $this->updates[spl_object_hash($entity)] = $entity;
        $this->addChangeSet($entity, $changeSet);

        return $this;
    }

    /**
     * @param object $entity
     *
     * @return $this
     */
    public function addDeletion($entity)
    {
        $this->deletions[spl_object_hash($entity)] = $entity;

        return $this;
    }

    #[\Override]
    public function getScheduledEntityInsertions()
    {
        return $this->insertions;
    }

    #[\Override]
    public function getScheduledEntityUpdates()
    {
        return $this->updates;
    }

    #[\Override]
    public function getScheduledEntityDeletions()
    {
        return $this->deletions;
    }

    #[\Override]
    public function & getEntityChangeSet($entity)
    {
        return $this->changeSets[spl_object_hash($entity)];
    }

    /**
     * @param object $entity
     * @param array $changeSet
     */
    protected function addChangeSet($entity, $changeSet)
    {
        $this->changeSets[spl_object_hash($entity)] = $changeSet;
    }

    public function addCollectionUpdates($coll)
    {
        $this->collectionUpdates[] = $coll;
    }

    public function addCollectionDeletions($coll)
    {
        $this->collectionDeletions[] = $coll;
    }

    #[\Override]
    public function getScheduledCollectionUpdates()
    {
        return $this->collectionUpdates;
    }

    #[\Override]
    public function getScheduledCollectionDeletions()
    {
        return $this->collectionDeletions;
    }
}
