<?php

namespace Oro\Component\Layout;

final class BlockBuilder implements BlockBuilderInterface
{
    /** @var LayoutManipulatorInterface */
    private $layoutManipulator;

    /** @var ContextInterface */
    private $context;

    /** @var RawLayout */
    private $rawLayout;

    /** @var BlockTypeHelperInterface */
    private $typeHelper;

    /** @var string */
    private $id;

    public function __construct(
        LayoutManipulatorInterface $layoutManipulator,
        RawLayout $rawLayout,
        BlockTypeHelperInterface $typeHelper,
        ContextInterface $context
    ) {
        $this->layoutManipulator = $layoutManipulator;
        $this->rawLayout         = $rawLayout;
        $this->typeHelper        = $typeHelper;
        $this->context           = $context;
    }

    /**
     * Initializes the state of this object
     *
     * @param string $id The block id
     */
    public function initialize($id)
    {
        $this->id = $id;
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
    public function getLayoutManipulator()
    {
        return $this->layoutManipulator;
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
}
