<?php

namespace Oro\Component\Layout;

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
