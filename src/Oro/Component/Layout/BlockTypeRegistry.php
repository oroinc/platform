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
        if (!is_string($name)) {
            throw new Exception\UnexpectedTypeException($name, 'string');
        }

        if (!isset($this->types[$name])) {
            // Registers the block type.
            $type = $this->blockTypeFactory->createBlockType($name);

            if ($type->getName() !== $name) {
                throw new Exception\InvalidArgumentException(
                    sprintf(
                        'The block type name specified for the service does not match the actual name. Expected "%s",' .
                        ' given "%s"',
                        $name,
                        $type->getName()
                    )
                );
            }

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
        } catch (\InvalidArgumentException $e) {
            return false;
        } catch (\UnexpectedTypeException $e) {
            return false;
        }

        return true;
    }
}
