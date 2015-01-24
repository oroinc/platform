<?php

namespace Oro\Component\Layout;

/**
 * Base implementation of LayoutBuilderInterface
 * This is straightforward implementation with strict checking of all operations' arguments.
 * It means that:
 *  - several layout items with the same id cannot be added
 *  - only existing layout items can be removed
 *  - an alias must be added before you can use it
 *  - an alias can be added for existing item only
 *  - only existing alias can be removed
 */
class LayoutBuilder implements RawLayoutManipulatorInterface
{
    /** @var LayoutData */
    protected $layoutData;

    /** @var LayoutViewFactoryInterface */
    protected $layoutViewFactory;

    /** @var bool */
    protected $frozen = false;

    /** @var bool */
    protected $optionsFrozen = false;

    /**
     * @param LayoutViewFactoryInterface $layoutViewFactory
     */
    public function __construct(LayoutViewFactoryInterface $layoutViewFactory)
    {
        $this->layoutData        = new LayoutData();
        $this->layoutViewFactory = $layoutViewFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function add($id, $parentId = null, $blockType = null, array $options = [])
    {
        try {
            if ($this->frozen) {
                throw new Exception\LogicException('Cannot modify frozen layout.');
            }
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
            if ($this->frozen) {
                throw new Exception\LogicException('Cannot modify frozen layout.');
            }
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
            if ($this->frozen) {
                throw new Exception\LogicException('Cannot modify frozen layout.');
            }
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
            if ($this->frozen) {
                throw new Exception\LogicException('Cannot modify frozen layout.');
            }
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
            if ($this->optionsFrozen) {
                throw new Exception\LogicException('Cannot change frozen options.');
            }
            if (empty($optionName)) {
                throw new Exception\InvalidArgumentException('The option name must not be empty.');
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
            if ($this->optionsFrozen) {
                throw new Exception\LogicException('Cannot change frozen options.');
            }
            if (empty($optionName)) {
                throw new Exception\InvalidArgumentException('The option name must not be empty.');
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
    public function getLayout($rootId = null)
    {
        $this->optionsFrozen = true;

        $view = $this->layoutViewFactory->createView($this->layoutData, $rootId);

        $this->frozen = true;

        return new Layout($view);
    }

    /**
     * Checks whether the item with the given id exists in the layout
     *
     * @param string $id The item id
     *
     * @return bool
     */
    public function has($id)
    {
        return $this->layoutData->has($id);
    }

    /**
     * Checks whether the given item alias exists
     *
     * @param string $alias The item alias
     *
     * @return bool
     */
    public function hasAlias($alias)
    {
        return $this->layoutData->hasAlias($alias);
    }
}
