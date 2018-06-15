<?php

namespace Oro\Component\TestUtils\ORM\Mocks;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\UnitOfWork as BaseUnitOfWork;

class UnitOfWork extends BaseUnitOfWork
{
    protected $insertions = [];
    protected $deletions = [];
    protected $updates = [];
    protected $changeSets = [];

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

    /**
     * {@inheritdoc}
     */
    public function getScheduledEntityInsertions()
    {
        return $this->insertions;
    }

    /**
     * {@inheritdoc}
     */
    public function getScheduledEntityUpdates()
    {
        return $this->updates;
    }

    /**
     * {@inheritdoc}
     */
    public function getScheduledEntityDeletions()
    {
        return $this->deletions;
    }

    /**
     * {@inheritdoc}
     */
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
}
