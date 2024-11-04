<?php

namespace Oro\Component\Layout;

final class Block implements BlockInterface
{
    /** @var ContextInterface */
    private $context;

    /** @var DataAccessorInterface */
    private $data;

    /** @var RawLayout */
    private $rawLayout;

    /** @var BlockTypeHelperInterface */
    private $typeHelper;

    /** @var string */
    private $id;

    /** @var BlockInterface|null or false if not initialized */
    private $parent = false;

    public function __construct(
        RawLayout $rawLayout,
        BlockTypeHelperInterface $typeHelper,
        ContextInterface $context,
        DataAccessorInterface $data
    ) {
        $this->rawLayout  = $rawLayout;
        $this->typeHelper = $typeHelper;
        $this->context    = $context;
        $this->data       = $data;
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

    #[\Override]
    public function getId()
    {
        return $this->id;
    }

    #[\Override]
    public function getTypeName()
    {
        $blockType = $this->rawLayout->getProperty($this->id, RawLayout::BLOCK_TYPE, true);

        return $blockType instanceof BlockTypeInterface
            ? $blockType->getName()
            : $blockType;
    }

    #[\Override]
    public function getAliases()
    {
        return $this->rawLayout->getAliases($this->id);
    }

    #[\Override]
    public function getParent()
    {
        if ($this->parent === false) {
            $parentId = $this->rawLayout->getParentId($this->id);
            if ($parentId) {
                $this->parent = new self($this->rawLayout, $this->typeHelper, $this->context, $this->data);
                $this->parent->initialize($parentId);
            } else {
                $this->parent = null;
            }
        }

        return $this->parent;
    }

    #[\Override]
    public function getOptions()
    {
        return $this->rawLayout->getProperty($this->id, RawLayout::RESOLVED_OPTIONS, true);
    }

    #[\Override]
    public function getTypeHelper()
    {
        return $this->typeHelper;
    }

    #[\Override]
    public function getContext()
    {
        return $this->context;
    }

    #[\Override]
    public function getData()
    {
        return $this->data;
    }
}
