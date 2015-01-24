<?php

namespace Oro\Component\Layout;

/**
 * Base implementation of RawLayoutAccessorInterface
 * This is straightforward implementation with strict checking of all operations' arguments.
 * It means that:
 *  - several layout items with the same id cannot be added
 *  - only existing layout items can be removed
 *  - an alias must be added before you can use it
 *  - an alias can be added for existing item only
 *  - only existing alias can be removed
 */
class LayoutBuilder implements RawLayoutAccessorInterface
{
    /** @var LayoutData */
    protected $layoutData;

    public function __construct()
    {
        $this->layoutData = new LayoutData();
    }

    /**
     * {@inheritdoc}
     */
    public function add($id, $parentId = null, $blockType = null, array $options = [])
    {
        try {
            $this->layoutData->add($id, $parentId, $blockType, $options);
        } catch (\Exception $e) {
            throw new Exception\LogicException(
                sprintf(
                    'Cannot add "%s" item to the layout. ParentId: %s. BlockType: %s. Reason: %s',
                    $id,
                    $parentId,
                    $blockType,
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
            $this->layoutData->remove($id);
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
    public function addAlias($alias, $id)
    {
        try {
            $this->layoutData->addAlias($alias, $id);
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
            $this->layoutData->removeAlias($alias);
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
            if (empty($optionName)) {
                throw new Exception\InvalidArgumentException('The option name must not be empty.');
            }
            if ($this->layoutData->hasProperty($id, LayoutData::RESOLVED_OPTIONS)) {
                throw new Exception\LogicException('Cannot change already resolved options.');
            }
            $options              = $this->layoutData->getProperty($id, LayoutData::OPTIONS);
            $options[$optionName] = $optionValue;
            $this->layoutData->setProperty($id, LayoutData::OPTIONS, $options);
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
            if (empty($optionName)) {
                throw new Exception\InvalidArgumentException('The option name must not be empty.');
            }
            if ($this->layoutData->hasProperty($id, LayoutData::RESOLVED_OPTIONS)) {
                throw new Exception\LogicException('Cannot change already resolved options.');
            }
            $options = $this->layoutData->getProperty($id, LayoutData::OPTIONS);
            unset($options[$optionName]);
            $this->layoutData->setProperty($id, LayoutData::OPTIONS, $options);
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
    public function has($id)
    {
        return $this->layoutData->has($id);
    }

    /**
     * {@inheritdoc}
     */
    public function hasAlias($alias)
    {
        return $this->layoutData->hasAlias($alias);
    }

    /**
     * {@inheritdoc}
     */
    public function getOptions($id)
    {
        try {
            return $this->layoutData->getProperty($id, LayoutData::OPTIONS);
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
     * @return LayoutData
     */
    public function getLayout()
    {
        return $this->layoutData;
    }
}
