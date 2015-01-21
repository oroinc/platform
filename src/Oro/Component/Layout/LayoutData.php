<?php

namespace Oro\Component\Layout;

class LayoutData
{
    /**
     * @var array
     *
     * Example:
     *  [
     *      'root' => [
     *          'path'       => [],
     *          'block_type' => 'root',
     *          'options'    => []
     *      ],
     *      'header' => [
     *          'path'       => ['root'],
     *          'block_type' => 'panel',
     *          'options'    => []
     *      ],
     *      'menu' => [
     *          'path'       => ['root', 'header'],
     *          'block_type' => 'menu',
     *          'options'    => []
     *      ]
     *  ]
     */
    protected $items = [];

    /**
     * @var array
     *
     * Example:
     *  [
     *      'root' => [
     *          'property1' => 'some value',
     *      ],
     *      'header' => [
     *          'property1' => 'some value',
     *          'property2' => 123
     *      ]
     *  ]
     */
    protected $itemProperties = [];

    /** @var HierarchyCollection */
    protected $hierarchy;

    /** @var AliasCollection */
    protected $aliases;

    public function __construct()
    {
        $this->hierarchy = new HierarchyCollection();
        $this->aliases   = new AliasCollection();
    }

    /**
     * Returns the id of the root layout item
     *
     * @return string
     */
    public function getRootItemId()
    {
        return $this->hierarchy->getRootId();
    }

    /**
     * Returns real id of the layout item
     *
     * @param string $id The item id or alias
     *
     * @return string The item id
     */
    public function resolveItemId($id)
    {
        return $this->aliases->has($id)
            ? $this->aliases->getId($id)
            : $id;
    }

    /**
     * Checks if the layout item with the given id exists
     *
     * @param string $id The item id
     *
     * @return bool
     */
    public function hasItem($id)
    {
        return isset($this->items[$id]);
    }

    /**
     * Returns the layout item by its id
     *
     * @param string $id The item id
     *
     * @return array
     *
     * @throws Exception\ItemNotFoundException if an item does not exist
     */
    public function getItem($id)
    {
        if (!isset($this->items[$id])) {
            throw new Exception\ItemNotFoundException(sprintf('The "%" item does not exist.', $id));
        }

        return $this->items[$id];
    }

    /**
     * Adds a new item to the layout
     *
     * @param string $id        The item id
     * @param string $parentId  The parent item id or alias
     * @param string $blockType The block type associated with the item
     * @param array  $options   The item options
     */
    public function addItem($id, $parentId = null, $blockType = null, array $options = [])
    {
        if (empty($id)) {
            throw new Exception\InvalidArgumentException('The item id must not be empty.');
        }
        $id = $this->resolveItemId($id);
        if ($this->hasItem($id)) {
            throw new Exception\ItemAlreadyExistsException(
                sprintf(
                    'The "%" item already exists.'
                    . ' Remove existing item before add the new item with the same id.',
                    $id
                )
            );
        }
        if (empty($parentId)) {
            if (!$this->hierarchy->isEmpty()) {
                throw new Exception\LogicException(
                    sprintf(
                        'The "%" item cannot be the root item'
                        . ' because another root item ("%s") already exists.',
                        $id,
                        $this->hierarchy->getRootId()
                    )
                );
            }
        } elseif (empty($blockType)) {
            throw new Exception\LogicException(
                sprintf('The block type for "%s" item must not be empty.', $id)
            );
        }

        $path = [];
        if (!empty($parentId)) {
            $parentId   = $this->resolveItemId($parentId);
            $parentItem = $this->getItem($parentId);
            $path       = $parentItem['path'];
        }
        $path[] = $id;

        $this->hierarchy->add($path, $id);
        $this->items[$id] = [
            'path'       => $path,
            'block_type' => $blockType,
            'options'    => $options
        ];
    }

    /**
     * Removes the item from the layout
     *
     * @param string $id The item id
     */
    public function removeItem($id)
    {
        if (empty($id)) {
            throw new Exception\InvalidArgumentException('The item id must not be empty.');
        }
        if (!isset($this->items[$id])) {
            return;
        }

        $path = $this->items[$id]['path'];

        // remove item from hierarchy
        $this->hierarchy->remove($path);
        // remove item
        unset($this->items[$id]);
        unset($this->itemProperties[$id]);
        $this->aliases->removeById($id);
        // remove all children
        $pathLength    = count($path);
        $pathLastIndex = $pathLength - 1;
        $ids           = array_keys($this->items);
        foreach ($ids as $itemId) {
            $currentPath = $this->items[$itemId]['path'];
            if (count($currentPath) > $pathLength && $currentPath[$pathLastIndex] === $id) {
                unset($this->items[$itemId]);
                unset($this->itemProperties[$itemId]);
                $this->aliases->removeById($itemId);
            }
        }
    }

    /**
     * Checks if the layout item has the given additional property
     *
     * @param string $id    The item id
     * @param string $name  The property name
     *
     * @return boolean
     *
     * @throws Exception\ItemNotFoundException if an item does not exist
     */
    public function hasItemProperty($id, $name)
    {
        if (!isset($this->items[$id])) {
            throw new Exception\ItemNotFoundException(sprintf('The "%" item does not exist.', $id));
        }

        if (!isset($this->itemProperties[$id])) {
            return false;
        }

        return isset($this->itemProperties[$id][$name]) || array_key_exists($name, $this->itemProperties[$id]);
    }

    /**
     * Gets a value of an additional property for the layout item
     *
     * @param string $id    The item id
     * @param string $name  The property name
     *
     * @return mixed
     *
     * @throws Exception\ItemNotFoundException if an item does not exist
     */
    public function getItemProperty($id, $name)
    {
        return $this->hasItemProperty($id, $name)
            ? $this->itemProperties[$id][$name]
            : null;
    }

    /**
     * Sets a value of an additional property for the layout item
     *
     * @param string $id    The item id
     * @param string $name  The property name
     * @param mixed  $value The property value
     *
     * @throws Exception\ItemNotFoundException if an item does not exist
     */
    public function setItemProperty($id, $name, $value)
    {
        if (!isset($this->items[$id])) {
            throw new Exception\ItemNotFoundException(sprintf('The "%" item does not exist.', $id));
        }

        $this->itemProperties[$id][$name] = $value;
    }

    /**
     * Creates an alias for the specified layout item
     *
     * @param string $alias A string that can be used instead of the item id
     * @param string $id    The item id
     */
    public function addItemAlias($alias, $id)
    {
        if (empty($alias)) {
            throw new Exception\InvalidArgumentException('The item alias must not be empty.');
        }
        if (empty($id)) {
            throw new Exception\InvalidArgumentException('The item id must not be empty.');
        }
        if (isset($this->items[$alias])) {
            throw new Exception\ItemAlreadyExistsException(
                sprintf(
                    'The "%" sting cannot be used as an alias for "%s" item'
                    . ' because another item with this id exists.',
                    $alias,
                    $id
                )
            );
        }

        $this->aliases->add($alias, $id);
    }

    /**
     * Removes the layout item alias
     *
     * @param string $alias The item alias
     */
    public function removeItemAlias($alias)
    {
        if (empty($alias)) {
            throw new Exception\InvalidArgumentException('The item alias must not be empty.');
        }

        $this->aliases->remove($alias);
    }

    /**
     * Returns the layout items hierarchy from the given path
     *
     * @param string[] $path
     *
     * @return array
     */
    public function getHierarchy(array $path = [])
    {
        return $this->hierarchy->get($path);
    }

    /**
     * Returns an iterator which can be used to get ids of all layout items
     * located under the given path.
     * Iteration is performed from parent to child.
     *
     * @param string[] $rootPath
     *
     * @return \Iterator
     */
    public function getHierarchyIterator(array $rootPath = [])
    {
        return new \RecursiveIteratorIterator(
            new KeyAsValueRecursiveArrayIterator($this->getHierarchy($rootPath)),
            \RecursiveIteratorIterator::SELF_FIRST
        );
    }
}
