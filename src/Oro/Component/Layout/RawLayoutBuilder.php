<?php

namespace Oro\Component\Layout;

/**
 * This is straightforward implementation with strict checking of all operations' arguments.
 * It means that:
 *  - several layout items with the same id cannot be added
 *  - only existing layout items can be removed
 *  - an alias must be added before you can use it
 *  - an alias can be added for existing item only
 *  - only existing alias can be removed
 *
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class RawLayoutBuilder implements RawLayoutBuilderInterface
{
    /** @var RawLayout */
    protected $rawLayout;

    /** @var BlockOptionsManipulatorInterface */
    protected $optionsManipulator;

    /**
     * @param BlockOptionsManipulatorInterface $optionsManipulator
     */
    public function __construct(BlockOptionsManipulatorInterface $optionsManipulator = null)
    {
        $this->rawLayout          = new RawLayout();
        $this->optionsManipulator = $optionsManipulator ?: new BlockOptionsManipulator();
        $this->optionsManipulator->setRawLayout($this->rawLayout);
    }

    /**
     * {@inheritdoc}
     */
    public function add(
        $id,
        $parentId,
        $blockType,
        array $options = [],
        $siblingId = null,
        $prepend = null
    ) {
        try {
            $this->validateBlockType($blockType);
            $this->rawLayout->add($id, $parentId, $blockType, $options, $siblingId, $prepend);
        } catch (\Exception $e) {
            throw new Exception\LogicException(
                sprintf(
                    'Cannot add "%s" item to the layout. ParentId: %s. BlockType: %s. SiblingId: %s. Reason: %s',
                    $id,
                    $parentId,
                    $blockType instanceof BlockTypeInterface ? $blockType->getName() : $blockType,
                    $siblingId,
                    $e->getMessage()
                ),
                0,
                $e
            );
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function remove($id)
    {
        try {
            $this->rawLayout->remove($id);
        } catch (\Exception $e) {
            throw new Exception\LogicException(
                sprintf(
                    'Cannot remove "%s" item from the layout. Reason: %s',
                    $id,
                    $e->getMessage()
                ),
                0,
                $e
            );
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function move($id, $parentId = null, $siblingId = null, $prepend = null)
    {
        try {
            $this->rawLayout->move($id, $parentId, $siblingId, $prepend);
        } catch (\Exception $e) {
            throw new Exception\LogicException(
                sprintf(
                    'Cannot move "%s" item. ParentId: %s. SiblingId: %s. Reason: %s',
                    $id,
                    $parentId,
                    $siblingId,
                    $e->getMessage()
                ),
                0,
                $e
            );
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function addAlias($alias, $id)
    {
        try {
            $this->rawLayout->addAlias($alias, $id);
        } catch (\Exception $e) {
            throw new Exception\LogicException(
                sprintf(
                    'Cannot add "%s" alias for "%s" item. Reason: %s',
                    $alias,
                    $id,
                    $e->getMessage()
                ),
                0,
                $e
            );
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function removeAlias($alias)
    {
        try {
            $this->rawLayout->removeAlias($alias);
        } catch (\Exception $e) {
            throw new Exception\LogicException(
                sprintf(
                    'Cannot remove "%s" alias. Reason: %s',
                    $alias,
                    $e->getMessage()
                ),
                0,
                $e
            );
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function setOption($id, $optionName, $optionValue)
    {
        try {
            if (!$optionName) {
                throw new Exception\InvalidArgumentException('The option name must not be empty.');
            }
            if ($this->rawLayout->hasProperty($id, RawLayout::RESOLVED_OPTIONS)) {
                throw new Exception\LogicException('Cannot change already resolved options.');
            }
            $this->optionsManipulator->setOption($id, $optionName, $optionValue);
        } catch (\Exception $e) {
            throw new Exception\LogicException(
                sprintf(
                    'Cannot set a value for "%s" option for "%s" item. Reason: %s',
                    $optionName,
                    $id,
                    $e->getMessage()
                ),
                0,
                $e
            );
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function appendOption($id, $optionName, $optionValue)
    {
        try {
            if (!$optionName) {
                throw new Exception\InvalidArgumentException('The option name must not be empty.');
            }
            if ($this->rawLayout->hasProperty($id, RawLayout::RESOLVED_OPTIONS)) {
                throw new Exception\LogicException('Cannot change already resolved options.');
            }
            $this->optionsManipulator->appendOption($id, $optionName, $optionValue);
        } catch (\Exception $e) {
            throw new Exception\LogicException(
                sprintf(
                    'Cannot append a value for "%s" option for "%s" item. Reason: %s',
                    $optionName,
                    $id,
                    $e->getMessage()
                ),
                0,
                $e
            );
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function subtractOption($id, $optionName, $optionValue)
    {
        try {
            if (!$optionName) {
                throw new Exception\InvalidArgumentException('The option name must not be empty.');
            }
            if ($this->rawLayout->hasProperty($id, RawLayout::RESOLVED_OPTIONS)) {
                throw new Exception\LogicException('Cannot change already resolved options.');
            }
            $this->optionsManipulator->subtractOption($id, $optionName, $optionValue);
        } catch (\Exception $e) {
            throw new Exception\LogicException(
                sprintf(
                    'Cannot subtract a value for "%s" option for "%s" item. Reason: %s',
                    $optionName,
                    $id,
                    $e->getMessage()
                ),
                0,
                $e
            );
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function replaceOption($id, $optionName, $oldOptionValue, $newOptionValue)
    {
        try {
            if (!$optionName) {
                throw new Exception\InvalidArgumentException('The option name must not be empty.');
            }
            if ($this->rawLayout->hasProperty($id, RawLayout::RESOLVED_OPTIONS)) {
                throw new Exception\LogicException('Cannot change already resolved options.');
            }
            $this->optionsManipulator->replaceOption($id, $optionName, $oldOptionValue, $newOptionValue);
        } catch (\Exception $e) {
            throw new Exception\LogicException(
                sprintf(
                    'Cannot replace a value for "%s" option for "%s" item. Reason: %s',
                    $optionName,
                    $id,
                    $e->getMessage()
                ),
                0,
                $e
            );
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function removeOption($id, $optionName)
    {
        try {
            if (!$optionName) {
                throw new Exception\InvalidArgumentException('The option name must not be empty.');
            }
            if ($this->rawLayout->hasProperty($id, RawLayout::RESOLVED_OPTIONS)) {
                throw new Exception\LogicException('Cannot change already resolved options.');
            }
            $this->optionsManipulator->removeOption($id, $optionName);
        } catch (\Exception $e) {
            throw new Exception\LogicException(
                sprintf(
                    'Cannot remove "%s" option for "%s" item. Reason: %s',
                    $optionName,
                    $id,
                    $e->getMessage()
                ),
                0,
                $e
            );
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function changeBlockType($id, $blockType, $optionsCallback = null)
    {
        try {
            if ($this->rawLayout->hasProperty($id, RawLayout::RESOLVED_OPTIONS)) {
                throw new Exception\LogicException('Cannot change the block type if options are already resolved.');
            }
            $this->validateBlockType($blockType);
            $this->rawLayout->setProperty($id, RawLayout::BLOCK_TYPE, $blockType);
            if ($optionsCallback !== null) {
                if (!is_callable($optionsCallback)) {
                    throw new Exception\UnexpectedTypeException($optionsCallback, 'callable', 'optionsCallback');
                }
                $options = $this->rawLayout->getProperty($id, RawLayout::OPTIONS);
                $options = call_user_func($optionsCallback, $options);
                $this->rawLayout->setProperty($id, RawLayout::OPTIONS, $options);
            }
        } catch (\Exception $e) {
            throw new Exception\LogicException(
                sprintf(
                    'Cannot change block type to "%s" for "%s" item. Reason: %s',
                    $blockType instanceof BlockTypeInterface ? $blockType->getName() : $blockType,
                    $id,
                    $e->getMessage()
                ),
                0,
                $e
            );
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function setBlockTheme($themes, $id = null)
    {
        try {
            if (!$id) {
                $id = $this->rawLayout->getRootId();
            }
            $this->rawLayout->setBlockTheme($id, $themes);
        } catch (\Exception $e) {
            throw new Exception\LogicException(
                sprintf(
                    'Cannot set theme(s) for "%s" item. Reason: %s',
                    $id,
                    $e->getMessage()
                ),
                0,
                $e
            );
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function setFormTheme($themes)
    {
        $this->rawLayout->setFormTheme($themes);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function clear()
    {
        $this->rawLayout->clear();

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function isEmpty()
    {
        return $this->rawLayout->isEmpty();
    }

    /**
     * {@inheritdoc}
     */
    public function has($id)
    {
        return $this->rawLayout->has($id);
    }

    /**
     * {@inheritdoc}
     */
    public function resolveId($id)
    {
        return $this->rawLayout->resolveId($id);
    }

    /**
     * {@inheritdoc}
     */
    public function getParentId($id)
    {
        return $this->rawLayout->getParentId($id);
    }

    /**
     * {@inheritdoc}
     */
    public function isParentFor($parentId, $id)
    {
        return
            $this->rawLayout->has($parentId)
            && $this->rawLayout->has($id)
            && $this->rawLayout->getParentId($id) === $this->rawLayout->resolveId($parentId);
    }

    /**
     * {@inheritdoc}
     */
    public function hasAlias($alias)
    {
        return $this->rawLayout->hasAlias($alias);
    }

    /**
     * {@inheritdoc}
     */
    public function getAliases($id)
    {
        return $this->rawLayout->getAliases($id);
    }

    /**
     * {@inheritdoc}
     */
    public function getType($id)
    {
        try {
            $blockType = $this->rawLayout->getProperty($id, RawLayout::BLOCK_TYPE);

            return $blockType instanceof BlockTypeInterface
                ? $blockType->getName()
                : $blockType;
        } catch (\Exception $e) {
            throw new Exception\LogicException(
                sprintf(
                    'Cannot get block type for "%s" item. Reason: %s',
                    $id,
                    $e->getMessage()
                ),
                0,
                $e
            );
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getOptions($id)
    {
        try {
            return $this->rawLayout->getProperty($id, RawLayout::OPTIONS);
        } catch (\Exception $e) {
            throw new Exception\LogicException(
                sprintf(
                    'Cannot get options for "%s" item. Reason: %s',
                    $id,
                    $e->getMessage()
                ),
                0,
                $e
            );
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getRawLayout()
    {
        return $this->rawLayout;
    }


    /**
     * Checks if the given value can be used as the block type name
     *
     * @param string|BlockTypeInterface $blockType The block type
     *
     * @throws Exception\InvalidArgumentException if the block type name is not valid
     */
    protected function validateBlockType($blockType)
    {
        if (!$blockType) {
            throw new Exception\InvalidArgumentException('The block type name must not be empty.');
        }
        if (!$blockType instanceof BlockTypeInterface) {
            if (!is_string($blockType)) {
                throw new Exception\UnexpectedTypeException($blockType, 'string or BlockTypeInterface', 'blockType');
            }
            if (!preg_match('/^[a-z][a-z0-9_]*$/iD', $blockType)) {
                throw new Exception\InvalidArgumentException(
                    sprintf(
                        'The "%s" string cannot be used as the name of the block type '
                        . 'because it contains illegal characters. '
                        . 'The valid block type name should start with a letter and only contain '
                        . 'letters, numbers and underscores ("_").',
                        $blockType
                    )
                );
            }
        }
    }
}
