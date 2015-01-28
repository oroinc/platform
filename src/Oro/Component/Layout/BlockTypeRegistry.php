<?php

namespace Oro\Component\Layout;

use Oro\Component\Layout\Exception;

class BlockTypeRegistry implements BlockTypeRegistryInterface
{
    /** @var BlockTypeInterface[] */
    private $types = [];

    /** @var BlockTypeFactoryInterface */
    private $blockTypeFactory;

    /**
     * @param BlockTypeFactoryInterface $blockTypeFactory The factory for created block.
     */
    public function __construct(BlockTypeFactoryInterface $blockTypeFactory)
    {
        $this->blockTypeFactory = $blockTypeFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockType($name)
    {
        if (empty($name)) {
            throw new Exception\InvalidArgumentException('The block type name must not be empty.');
        }
        if (!is_string($name)) {
            throw new Exception\UnexpectedTypeException($name, 'string');
        }

        if (!isset($this->types[$name])) {
            $type = $this->blockTypeFactory->createBlockType($name);
            if (!$type) {
                throw new Exception\LogicException(
                    sprintf('The block type named "%s" was not found.', $name)
                );
            }
            if ($type->getName() !== $name) {
                throw new Exception\LogicException(
                    sprintf(
                        'The block type name does not match the name declared in the class implementing this type. '
                        . 'Expected "%s", given "%s".',
                        $name,
                        $type->getName()
                    )
                );
            }

            // add the created block type to the local cache
            $this->types[$name] = $type;
        }

        return $this->types[$name];
    }

    /**
     * {@inheritdoc}
     */
    public function hasBlockType($name)
    {
        if (isset($this->types[$name])) {
            return true;
        }

        try {
            $this->getBlockType($name);
        } catch (Exception\ExceptionInterface $e) {
            return false;
        }

        return true;
    }
}
