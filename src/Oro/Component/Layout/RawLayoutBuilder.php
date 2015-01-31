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
 */
class RawLayoutBuilder implements RawLayoutBuilderInterface
{
    /** @var RawLayout */
    protected $rawLayout;

    public function __construct()
    {
        $this->rawLayout = new RawLayout();
    }

    /**
     * {@inheritdoc}
     */
    public function add(
        $id,
        $parentId = null,
        $blockType = null,
        array $options = [],
        $siblingId = null,
        $prepend = false
    ) {
        try {
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
    public function move($id, $parentId = null, $siblingId = null, $prepend = false)
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
            $options              = $this->rawLayout->getProperty($id, RawLayout::OPTIONS);
            $options[$optionName] = $optionValue;
            $this->rawLayout->setProperty($id, RawLayout::OPTIONS, $options);
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
    public function removeOption($id, $optionName)
    {
        try {
            if (!$optionName) {
                throw new Exception\InvalidArgumentException('The option name must not be empty.');
            }
            if ($this->rawLayout->hasProperty($id, RawLayout::RESOLVED_OPTIONS)) {
                throw new Exception\LogicException('Cannot change already resolved options.');
            }
            $options = $this->rawLayout->getProperty($id, RawLayout::OPTIONS);
            unset($options[$optionName]);
            $this->rawLayout->setProperty($id, RawLayout::OPTIONS, $options);
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
    public function clear()
    {
        $this->rawLayout->clear();
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
}
