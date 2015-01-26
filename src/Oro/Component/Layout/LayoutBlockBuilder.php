<?php

namespace Oro\Component\Layout;

class LayoutBlockBuilder implements BlockBuilderInterface
{
    /** @var LayoutManipulatorInterface */
    protected $layoutManipulator;

    /** @var ContextInterface */
    protected $context;

    /** @var string */
    protected $blockId;

    /**
     * @param LayoutManipulatorInterface $layoutManipulator
     * @param ContextInterface           $context
     */
    public function __construct(LayoutManipulatorInterface $layoutManipulator, ContextInterface $context)
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
    public function getLayoutBuilder()
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
