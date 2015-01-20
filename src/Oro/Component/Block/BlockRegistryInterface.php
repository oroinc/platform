<?php

namespace Oro\Component\Block;

use Symfony\Component\Form\Exception\ExceptionInterface;
use Symfony\Component\Form\Exception\UnexpectedTypeException;

interface BlockRegistryInterface
{
    /**
     * Returns a block type by name.
     *
     * @param string $name The name of the block type
     *
     * @return BlockTypeInterface
     *
     * @throws UnexpectedTypeException
     */
    public function getType($name);

    /**
     * Returns whether the given block type is supported.
     *
     * @param string $name The name of the block type
     *
     * @return bool Whether the block type is supported
     *
     * @throws ExceptionInterface
     */
    public function hasType($name);
}
