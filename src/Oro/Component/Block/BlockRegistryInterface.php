<?php

namespace Oro\Component\Block;

use Symfony\Component\Form\Exception\ExceptionInterface;
use Symfony\Component\Form\Exception\UnexpectedTypeException;

interface BlockRegistryInterface
{
    /**
     * Returns a block type by name.
     * This methods registers the block type.
     *
     * @param string $name The name of the type
     *
     * @return BlockTypeInterface The type
     *
     * @throws UnexpectedTypeException
     */
    public function getType($name);

    /**
     * Returns whether the given block type is supported.
     *
     * @param string $name The name of the type
     *
     * @return bool Whether the type is supported
     *
     * @throws ExceptionInterface
     */
    public function hasType($name);
}
