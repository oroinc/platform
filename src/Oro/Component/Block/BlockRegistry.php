<?php

namespace Oro\Component\Block;

use Symfony\Component\Form\Exception\ExceptionInterface;
use Symfony\Component\Form\Exception\UnexpectedTypeException;

class BlockRegistry implements BlockRegistryInterface
{
    /**
     * @var BlockTypeInterface[]
     */
    private $types = array();

    /**
     * @var BlockTypeFactoryInterface
     */
    private $blockTypeFactory;

    /**
     * Constructor.
     *
     * @param BlockTypeFactoryInterface $blockTypeFactory The factory for created block.
     */
    public function __construct(BlockTypeFactoryInterface $blockTypeFactory)
    {
        $this->blockTypeFactory = $blockTypeFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function getType($name)
    {
        if (!is_string($name)) {
            throw new UnexpectedTypeException($name, 'string');
        }

        if (!isset($this->types[$name])) {
            $this->types[$name] = $this->blockTypeFactory->createBlockType($name);
        }

        return $this->types[$name];
    }

    /**
     * {@inheritdoc}
     */
    public function hasType($name)
    {
        if (isset($this->types[$name])) {
            return true;
        }

        try {
            $this->getType($name);
        } catch (ExceptionInterface $e) {
            return false;
        }

        return true;
    }
}
