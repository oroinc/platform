<?php

namespace Oro\Bundle\UIBundle\Model;

class TreeItem
{
    /**
     * @param string $key
     * @param string $label
     */
    public function __construct($key, $label = '')
    {
        $this->key = $key;
        $this->label = $label;
    }

    /** @var int|string */
    private $key;

    /** @var string */
    private $label;

    /** @var TreeItem */
    private $parent;

    /** @var TreeItem[] */
    private $children = [];

    /**
     * @return int|string
     */
    public function getKey()
    {
        return $this->key;
    }

    /**
     * @return string
     */
    public function getLabel()
    {
        return $this->label;
    }

    /**
     * @return TreeItem
     */
    public function getParent()
    {
        return $this->parent;
    }

    /**
     * @param TreeItem $parent
     *
     * @return TreeItem
     */
    public function setParent(TreeItem $parent)
    {
        if ($parent === $this) {
            throw new \InvalidArgumentException('Item cannot be a child of itself');
        }

        $this->parent = $parent;
        $this->parent->addChild($this);

        return $this;
    }

    /**
     * @return TreeItem[]
     */
    public function getChildren()
    {
        return $this->children;
    }

    /**
     * @param TreeItem $child
     *
     * @return TreeItem
     */
    public function addChild(TreeItem $child)
    {
        $this->children[$child->key] = $child;

        return $this;
    }

    /**
     * @return int
     */
    public function getLevel()
    {
        return $this->parent ? $this->parent->getLevel() + 1 : 0;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->label;
    }
}
