<?php

namespace Oro\Component\Layout;

class LayoutBlock implements BlockInterface
{
    /** @var ContextInterface */
    protected $context;

    /** @var string */
    protected $blockId;

    /**
     * @param ContextInterface $context
     */
    public function __construct(ContextInterface $context)
    {
        $this->context = $context;
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
    public function getContext()
    {
        return $this->context;
    }
}
