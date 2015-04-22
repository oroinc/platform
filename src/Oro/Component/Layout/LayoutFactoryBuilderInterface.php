<?php

namespace Oro\Component\Layout;

use Oro\Component\Layout\Extension\ExtensionInterface;

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
