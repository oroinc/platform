<?php

namespace Oro\Component\Layout;

class BlockBuilder implements BlockBuilderInterface
{
    /** @var LayoutStructureManipulatorInterface */
    protected $layoutManipulator;

    /** @var ContextInterface */
    protected $context;

    /** @var string */
    protected $blockId;

    /**
     * @param LayoutStructureManipulatorInterface $layoutManipulator
     * @param ContextInterface                    $context
     */
    public function __construct(LayoutStructureManipulatorInterface $layoutManipulator, ContextInterface $context)
    {
        $this->layoutManipulator = $layoutManipulator;
        $this->context           = $context;
    }

    /**
     * Initializes the state of this object
     *
     * @param string $blockId
     */
    public function initialize($blockId)
    {
        $this->blockId = $blockId;
    }

    /**
     * {@inheritdoc}
     */
    public function getId()
    {
        return $this->blockId;
    }

    /**
     * {@inheritdoc}
     */
    public function getLayoutManipulator()
    {
        return $this->layoutManipulator;
    }

    /**
     * {@inheritdoc}
     */
    public function getContext()
    {
        return $this->context;
    }
}
