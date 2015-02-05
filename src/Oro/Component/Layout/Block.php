<?php

namespace Oro\Component\Layout;

class Block implements BlockInterface
{
    /** @var ContextInterface */
    protected $context;

    /** @var RawLayout */
    protected $rawLayout;

    /** @var string */
    protected $id;

    /** @var BlockInterface|null or false if not initialized */
    protected $parent = false;

    /**
     * @param ContextInterface $context
     * @param RawLayout        $rawLayout
     */
    public function __construct(RawLayout $rawLayout, ContextInterface $context)
    {
        $this->rawLayout = $rawLayout;
        $this->context   = $context;
    }

    /**
     * Initializes the state of this object
     *
     * @param string $id The the block id
     */
    public function initialize($id)
    {
        $this->id     = $id;
        $this->parent = false;
    }

    /**
     * {@inheritdoc}
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        $blockType = $this->rawLayout->getProperty($this->id, RawLayout::BLOCK_TYPE, true);

        return $blockType instanceof BlockTypeInterface
            ? $blockType->getName()
            : $blockType;
    }

    /**
     * {@inheritdoc}
     */
    public function getAliases()
    {
        return $this->rawLayout->getAliases($this->id);
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        if ($this->parent === false) {
            $parentId = $this->rawLayout->getParentId($this->id);
            if ($parentId) {
                $this->parent = new self($this->rawLayout, $this->context);
                $this->parent->initialize($parentId);
            } else {
                $this->parent = null;
            }
        }

        return $this->parent;
    }

    /**
     * {@inheritdoc}
     */
    public function getOptions()
    {
        return $this->rawLayout->getProperty($this->id, RawLayout::RESOLVED_OPTIONS, true);
    }

    /**
     * {@inheritdoc}
     */
    public function getContext()
    {
        return $this->context;
    }
}
