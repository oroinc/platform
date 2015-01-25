<?php

namespace Oro\Component\Layout;

/**
 * This class id is a proxy for the real implementation of DeferredLayoutManipulatorInterface,
 * but in additional it can calculate the number od added/removed items
 */
class DeferredLayoutManipulatorWithChangeCounter implements DeferredLayoutManipulatorInterface
{
    /** @var DeferredLayoutManipulatorInterface */
    protected $layoutManipulator;

    /** @var int */
    protected $addCounter = 0;

    /** @var int */
    protected $removeCounter = 0;

    /**
     * @param DeferredLayoutManipulatorInterface $layoutManipulator
     */
    public function __construct(DeferredLayoutManipulatorInterface $layoutManipulator)
    {
        $this->layoutManipulator = $layoutManipulator;
    }

    /**
     * Returns the number of added items
     *
     * @return int
     */
    public function getNumberOfAddedItems()
    {
        return $this->addCounter;
    }

    /**
     * Returns the number of removed items
     *
     * @return int
     */
    public function getNumberOfRemovedItems()
    {
        return $this->removeCounter;
    }

    /**
     * Sets all counters to zero
     */
    public function resetCounters()
    {
        $this->addCounter    = 0;
        $this->removeCounter = 0;
    }

    /**
     * {@inheritdoc}
     */
    public function add($id, $parentId = null, $blockType = null, array $options = [])
    {
        $this->layoutManipulator->add($id, $parentId, $blockType, $options);
        $this->addCounter++;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function remove($id)
    {
        $this->layoutManipulator->remove($id);
        $this->removeCounter++;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function move($id, $parentId = null, $siblingId = null, $prepend = false)
    {
        $this->layoutManipulator->move($id, $parentId, $siblingId, $prepend);
        $this->addCounter++;
        $this->removeCounter++;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function addAlias($alias, $id)
    {
        $this->layoutManipulator->addAlias($alias, $id);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function removeAlias($alias)
    {
        $this->layoutManipulator->removeAlias($alias);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function applyChanges()
    {
        $this->layoutManipulator->applyChanges();
    }
}
