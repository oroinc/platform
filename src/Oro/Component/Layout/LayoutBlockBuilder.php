<?php

namespace Oro\Component\Layout;

class LayoutBlockBuilder implements BlockBuilderInterface
{
    /** @var string */
    protected $blockId;

    /** @var LayoutManipulatorInterface */
    protected $layoutManipulator;

    /**
     * @param string                     $blockId
     * @param LayoutManipulatorInterface $layoutManipulator
     */
    public function __construct($blockId, LayoutManipulatorInterface $layoutManipulator)
    {
        $this->blockId           = $blockId;
        $this->layoutManipulator = $layoutManipulator;
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
}
