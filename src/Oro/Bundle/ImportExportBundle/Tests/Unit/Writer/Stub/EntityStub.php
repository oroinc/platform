<?php

namespace Oro\Bundle\ImportExportBundle\Tests\Unit\Writer\Stub;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\PersistentCollection;

class EntityStub
{
    /**
     * @var EntityStub
     */
    private $entity;

    /**
     * @var ArrayCollection
     */
    private $newCollection;

    /**
     * @var PersistentCollection
     */
    private $dirtyPersistentCollection;

    /**
     * @var PersistentCollection
     */
    private $initializedPersistentCollection;

    /**
     * @var PersistentCollection
     */
    private $cleanNotInitializedPersistentCollection;

    /**
     * @var string
     */
    private $notReadable = 'some value';

    /**
     * @var bool
     */
    public $reloaded = false;

    /**
     * @return EntityStub
     */
    public function getEntity()
    {
        return $this->entity;
    }

    /**
     * @param EntityStub $entity
     * @return EntityStub
     */
    public function setEntity(EntityStub $entity)
    {
        $this->entity = $entity;
        return $this;
    }

    public function getNewCollection(): ArrayCollection
    {
        return $this->newCollection;
    }

    /**
     * @param ArrayCollection $newCollection
     * @return EntityStub
     */
    public function setNewCollection(ArrayCollection $newCollection)
    {
        $this->newCollection = $newCollection;
        return $this;
    }

    public function getDirtyPersistentCollection(): PersistentCollection
    {
        return $this->dirtyPersistentCollection;
    }

    /**
     * @param PersistentCollection $dirtyPersistentCollection
     * @return EntityStub
     */
    public function setDirtyPersistentCollection(PersistentCollection $dirtyPersistentCollection)
    {
        $this->dirtyPersistentCollection = $dirtyPersistentCollection;
        return $this;
    }

    public function getInitializedPersistentCollection(): PersistentCollection
    {
        return $this->initializedPersistentCollection;
    }

    /**
     * @param PersistentCollection $initializedPersistentCollection
     * @return EntityStub
     */
    public function setInitializedPersistentCollection(PersistentCollection $initializedPersistentCollection)
    {
        $this->initializedPersistentCollection = $initializedPersistentCollection;
        return $this;
    }

    /**
     * @return PersistentCollection
     */
    public function getCleanNotInitializedPersistentCollection()
    {
        return $this->cleanNotInitializedPersistentCollection;
    }

    /**
     * @param mixed $cleanNotInitializedPersistentCollection
     * @return EntityStub
     */
    public function setCleanNotInitializedPersistentCollection($cleanNotInitializedPersistentCollection)
    {
        $this->cleanNotInitializedPersistentCollection = $cleanNotInitializedPersistentCollection;
        return $this;
    }
}
