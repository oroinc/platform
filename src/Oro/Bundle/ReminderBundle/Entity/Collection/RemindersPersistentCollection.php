<?php

namespace Oro\Bundle\ReminderBundle\Entity\Collection;

use Doctrine\Common\Collections\AbstractLazyCollection;
use Doctrine\Common\Collections\ArrayCollection;

use Oro\Bundle\ReminderBundle\Model\ReminderDataInterface;
use Oro\Bundle\ReminderBundle\Entity\Repository\ReminderRepository;

class RemindersPersistentCollection extends AbstractLazyCollection
{
    /**
     * @var ReminderRepository
     */
    protected $repository;

    /**
     * @var string
     */
    protected $className;

    /**
     * @var mixed
     */
    protected $identifier;

    /**
     * @var ReminderDataInterface[]
     */
    protected $snapshot = array();

    /**
     * @var bool
     */
    protected $isDirty = false;

    /**
     * @param ReminderRepository $repository
     * @param string             $className
     * @param mixed              $identifier
     */
    public function __construct(ReminderRepository $repository, $className, $identifier)
    {
        $this->repository = $repository;
        $this->className  = $className;
        $this->identifier = $identifier;
    }

    /**
     * {@inheritdoc}
     */
    protected function doInitialize()
    {
        if (!$this->collection) {
            $reminders        = $this->repository->findRemindersByEntity($this->className, $this->identifier);
            $this->snapshot   = $reminders;
            $this->collection = new ArrayCollection($reminders);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function add($element)
    {
        $result = parent::add($element);
        $this->changed();
        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function clear()
    {
        $this->initialize();
        if (!$this->isEmpty()) {
            parent::clear();
            $this->changed();
        }
    }

    /**
     * {@inheritdoc}
     */
    public function remove($key)
    {
        $removed = parent::remove($key);
        if ($removed) {
            $this->changed();
        }
        return $removed;
    }

    /**
     * {@inheritdoc}
     */
    public function removeElement($element)
    {
        $removed = parent::removeElement($element);
        if ($removed) {
            $this->changed();
        }
        return $removed;
    }

    /**
     * {@inheritdoc}
     */
    public function set($key, $value)
    {
        if (null === $key) {
            parent::add($value);
        } else {
            parent::set($key, $value);
        }
        $this->changed();
    }

    /**
     * Get elements of collection when it was loaded from storage.
     *
     * @return ReminderDataInterface[]
     */
    public function getSnapshot()
    {
        return $this->snapshot;
    }

    /**
     * {@inheritdoc}
     */
    public function offsetExists($offset)
    {
        return $this->containsKey($offset);
    }

    /**
     * {@inheritdoc}
     */
    public function offsetGet($offset)
    {
        return $this->get($offset);
    }

    /**
     * {@inheritdoc}
     */
    public function offsetSet($offset, $value)
    {
        $this->set($offset, $value);
    }

    /**
     * {@inheritdoc}
     */
    public function offsetUnset($offset)
    {
        return $this->remove($offset);
    }

    /**
     * Marks this collection as changed/dirty.
     */
    protected function changed()
    {
        if ($this->isDirty) {
            return;
        }

        $this->isDirty = true;
    }

    /**
     * Checks if this collection is dirty which means its state needs to be synchronized with the database.
     *
     * @return boolean
     */
    public function isDirty()
    {
        return $this->isDirty;
    }

    /**
     * Get elements to delete
     *
     * @return array
     */
    public function getDeleteDiff()
    {
        return array_udiff_assoc(
            $this->snapshot,
            $this->toArray(),
            function ($a, $b) {
                return $a === $b ? 0 : 1;
            }
        );
    }

    /**
     * Get elements to insert
     *
     * @return array
     */
    public function getInsertDiff()
    {
        return array_udiff_assoc(
            $this->toArray(),
            $this->snapshot,
            function ($a, $b) {
                return $a === $b ? 0 : 1;
            }
        );
    }
}
