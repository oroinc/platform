<?php

namespace Oro\Component\Layout;

use Oro\Component\Layout\Extension\ExtensionInterface;

/**
 * Defines the contract for building a layout factory with extensions, types, and renderers.
 *
 * A layout factory builder uses a fluent interface to register extensions, block types, type extensions,
 * layout updates, and renderers, then builds and returns a fully configured layout factory.
 */
interface LayoutFactoryBuilderInterface
{
    /**
     * Registers a layout extension.
     *
     * @param ExtensionInterface $extension
     *
     * @return self
     */
    public function addExtension(ExtensionInterface $extension);

    /**
     * Registers a block type.
     *
     * @param BlockTypeInterface $type
     *
     * @return self
     */
    public function addType(BlockTypeInterface $type);

    /**
     * Registers a block type extension.
     *
     * @param BlockTypeExtensionInterface $typeExtension
     *
     * @return self
     */
    public function addTypeExtension(BlockTypeExtensionInterface $typeExtension);

    /**
     * Registers a layout update.
     *
     * @param string                $id
     * @param LayoutUpdateInterface $layoutUpdate
     *
     * @return self
     */
    public function addLayoutUpdate($id, LayoutUpdateInterface $layoutUpdate);

    /**
     * Registers a layout renderer.
     *
     * @param string                  $name
     * @param LayoutRendererInterface $renderer
     *
     * @return self
     */
    public function addRenderer($name, LayoutRendererInterface $renderer);

    /**
     * Sets the default layout renderer.
     *
     * @param string $name
     *
     * @return self
     */
    public function setDefaultRenderer($name);

    /**
     * Builds and returns the layout factory.
     *
     * @return LayoutFactoryInterface
     */
    public function getLayoutFactory();
}
