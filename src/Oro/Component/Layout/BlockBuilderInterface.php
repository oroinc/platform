<?php

namespace Oro\Component\Layout;

/**
 * Defines the contract for building blocks during layout construction.
 *
 * A block builder provides access to the block's identity, type, layout manipulator, type helper,
 * and context, allowing block types and extensions to configure and manipulate blocks during the build process.
 */
interface BlockBuilderInterface
{
    /**
     * Returns the id of the block
     *
     * @return string
     */
    public function getId();

    /**
     * Returns the name of the block type
     *
     * @return string
     */
    public function getTypeName();

    /**
     * Returns the layout manipulator
     *
     * @return LayoutManipulatorInterface
     */
    public function getLayoutManipulator();

    /**
     * Returns the block type helper
     *
     * @return BlockTypeHelperInterface
     */
    public function getTypeHelper();

    /**
     * Returns the layout building context
     *
     * @return ContextInterface
     */
    public function getContext();
}
