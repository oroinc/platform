<?php

namespace Oro\Component\TestUtils\ORM\Mocks;

use Doctrine\Common\Collections\AbstractLazyCollection;

/**
 * Special PersistentCollection mock used for testing purposes.
 */
class PersistentCollectionMock extends AbstractLazyCollection
{
    /**
     * The entity that owns this collection.
     *
     * @var object|null
     */
    private $owner;

    /**
     * The association mapping the collection belongs to.
     * This is currently either a OneToManyMapping or a ManyToManyMapping.
     *
     * @psalm-var array<string, mixed>|null
     */
    private array $association = [];

    private array $insertDiff;
    private array $deleteDiff;

    public function __construct(array $insertDiff = [], array $deleteDiff = [])
    {
        $this->insertDiff = $insertDiff;
        $this->deleteDiff = $deleteDiff;

        $this->initialized = true;
    }

    public function getInsertDiff()
    {
        return $this->insertDiff;
    }

    public function getDeleteDiff()
    {
        return $this->deleteDiff;
    }

    public function setOwner($entity, array $assoc): void
    {
        $this->owner            = $entity;
        $this->association      = $assoc;
    }

    public function getOwner()
    {
        return $this->owner;
    }

    public function getMapping()
    {
        return $this->association;
    }

    protected function doInitialize()
    {
    }
}
