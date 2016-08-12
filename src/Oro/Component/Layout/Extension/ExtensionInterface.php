<?php

namespace Oro\Component\Layout\Extension;

use Oro\Component\Layout\BlockTypeExtensionInterface;
use Oro\Component\Layout\BlockTypeInterface;
use Oro\Component\Layout\ContextConfiguratorInterface;
use Oro\Component\Layout\Exception;
use Oro\Component\Layout\LayoutItemInterface;
use Oro\Component\Layout\LayoutUpdateInterface;

/**
 * Interface for extensions which provide block types, block type extensions and layout updates.
 */
interface ExtensionInterface
{
    /**
     * Returns a block type by name.
     *
     * @param string $name The name of the block type
     *
     * @return BlockTypeInterface
     *
     * @throws Exception\InvalidArgumentException if the given type is not supported by this extension
     */
    public function getType($name);

    /**
     * Checks whether the given block type is supported.
     *
     * @param string $name The name of the block type
     *
     * @return bool true, if the given type is supported by this extension; otherwise, false
     */
    public function hasType($name);

    /**
     * Returns extensions for the given block type.
     *
     * @param string $name The name of the block type
     *
     * @return BlockTypeExtensionInterface[]
     */
    public function getTypeExtensions($name);

    /**
     * Checks whether this extension provides extensions for the given block type.
     *
     * @param string $name The name of the block type
     *
     * @return bool true, if the given block type has extensions; otherwise, false
     */
    public function hasTypeExtensions($name);

    /**
     * Returns layout updates for the given layout item.
     *
     * @param LayoutItemInterface $item
     *
     * @return \Oro\Component\Layout\LayoutUpdateInterface[]
     */
    public function getLayoutUpdates(LayoutItemInterface $item);

    /**
     * Checks whether this extension provides layout updates for the given layout item.
     *
     * @param LayoutItemInterface $item
     *
     * @return bool true, if the given layout item has additional layout updates; otherwise, false
     */
    public function hasLayoutUpdates(LayoutItemInterface $item);

    /**
     * Returns layout context configurators.
     *
     * @return ContextConfiguratorInterface[]
     */
    public function getContextConfigurators();

    /**
     * Checks whether this extension provides layout context configurators.
     *
     * @return bool true, if this extension has layout context configurators; otherwise, false
     */
    public function hasContextConfigurators();

    /**
     * Returns a data provider by name.
     *
     * @param string $name The name of the data provider
     *
     * @return object
     *
     * @throws Exception\InvalidArgumentException if the given data provider is not supported by this extension
     */
    public function getDataProvider($name);

    /**
     * Checks whether the given data provider is supported.
     *
     * @param string $name The name of the data provider
     *
     * @return bool true, if the given data provider is supported by this extension; otherwise, false
     */
    public function hasDataProvider($name);
}
