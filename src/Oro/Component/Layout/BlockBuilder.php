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

    /**
     * @var DataAccessorInterface
     */
    private $dataAccessor;

    /**
     * @param LayoutManipulatorInterface $layoutManipulator
     * @param RawLayout                $rawLayout
     * @param BlockTypeHelperInterface $typeHelper
     * @param ContextInterface         $context
     * @param DataAccessorInterface    $dataAccessor
     */
    public function __construct(
        LayoutManipulatorInterface $layoutManipulator,
        RawLayout $rawLayout,
        BlockTypeHelperInterface $typeHelper,
        ContextInterface $context,
        DataAccessorInterface $dataAccessor
    ) {
        $this->layoutManipulator = $layoutManipulator;
        $this->rawLayout         = $rawLayout;
        $this->typeHelper        = $typeHelper;
        $this->context           = $context;
        $this->dataAccessor      = $dataAccessor;
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
    public function getLayoutManipulator()
    {
        return $this->layoutManipulator;
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

    /**
     * {@inheritdoc}
     */
    public function getDataAccessor()
    {
        return $this->dataAccessor;
    }
}
