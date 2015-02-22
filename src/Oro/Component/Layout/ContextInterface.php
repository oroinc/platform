<?php

namespace Oro\Component\Layout;

use Symfony\Component\OptionsResolver\OptionsResolverInterface;

interface ContextInterface extends \ArrayAccess
{
    /**
     * Returns the context data resolver.
     *
     * @return OptionsResolverInterface
     */
    public function getDataResolver();

    /**
     * Resolves the context data according the data resolver.
     * The adding new item and removing existing ones are prohibited after the context data has been resolved.
     *
     * @throws Exception\LogicException if the context data cannot be resolved
     */
    public function resolve();

    /**
     * Indicates whether the context data are resolved or not.
     */
    public function isResolved();

    /**
     * Checks whether the context contains the given value.
     *
     * @param string $name The item name
     *
     * @return bool
     */
    public function has($name);

    /**
     * Gets a value stored in the context.
     *
     * @param string $name The item name
     *
     * @return mixed
     *
     * @throws \OutOfBoundsException if a item does not exist
     */
    public function get($name);

    /**
     * Gets a value stored in the context or the default value
     * if the context does not contain the requested item.
     *
     * @param string $name    The item name
     * @param mixed  $default The default value
     *
     * @return mixed
     */
    public function getOr($name, $default = null);

    /**
     * Sets a value in the context.
     *
     * @param string $name  The item name
     * @param mixed  $value The value to set
     *
     * @throws Exception\LogicException if a new value is added to already resolved context
     */
    public function set($name, $value);

    /**
     * Removes a value stored in the context.
     *
     * @param string $name The item name
     *
     * @throws Exception\LogicException if existing value is removed from already resolved context
     */
    public function remove($name);
}
