<?php

namespace Oro\Component\Layout;

/**
 * Defines the contract for creating layout-related components.
 *
 * A layout factory is responsible for creating and providing access to the layout registry,
 * renderer registry, and various builders needed to construct and manipulate layouts.
 */
interface LayoutFactoryInterface
{
    /**
     * Returns the layout registry.
     *
     * @return LayoutRegistryInterface
     */
    public function getRegistry();

    /**
     * Returns the layout renderer registry.
     *
     * @return LayoutRendererRegistryInterface
     */
    public function getRendererRegistry();

    /**
     * Returns a block type by name.
     *
     * @param string $name The block type name
     *
     * @return BlockTypeInterface
     */
    public function getType($name);

    /**
     * Creates the raw layout builder.
     *
     * @return RawLayoutBuilderInterface
     */
    public function createRawLayoutBuilder();

    /**
     * Creates the layout manipulator.
     *
     * @param RawLayoutBuilderInterface $rawLayoutBuilder
     *
     * @return DeferredLayoutManipulatorInterface
     */
    public function createLayoutManipulator(RawLayoutBuilderInterface $rawLayoutBuilder);

    /**
     * Creates the block factory.
     *
     * @param DeferredLayoutManipulatorInterface $layoutManipulator
     *
     * @return BlockFactoryInterface
     */
    public function createBlockFactory(DeferredLayoutManipulatorInterface $layoutManipulator);

    /**
     * Creates the layout builder.
     *
     * @return LayoutBuilderInterface
     */
    public function createLayoutBuilder();
}
