<?php

namespace Oro\Component\Layout;

final class Block implements BlockInterface
{
    /** @var ContextInterface */
    private $context;

    /** @var RawLayout */
    private $rawLayout;

    /** @var BlockTypeHelperInterface */
    private $typeHelper;

    /** @var string */
    private $id;

    /** @var BlockInterface|null or false if not initialized */
    private $parent = false;

    /**
     * @param RawLayout                $rawLayout
     * @param BlockTypeHelperInterface $typeHelper
     * @param ContextInterface         $context
     */
    public function __construct(
        RawLayout $rawLayout,
        BlockTypeHelperInterface $typeHelper,
        ContextInterface $context
    ) {
        $this->rawLayout  = $rawLayout;
        $this->typeHelper = $typeHelper;
        $this->context    = $context;
    }

    /**
     * Initializes the state of this object
     *
     * @param string $id The block id
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
    public function getTypeName()
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
                $this->parent = new self($this->rawLayout, $this->typeHelper, $this->context);
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
    public function getTypeHelper()
    {
        return $this->typeHelper;
    }

    /**
     * {@inheritdoc}
     */
    public function getContext()
    {
        return $this->context;
    }
}
