<?php

namespace Oro\Component\Layout;

class LayoutData
{
    /** The name of the block type */
    const BLOCK_TYPE = 'block_type';

    /** Additional options which are used for building the layout block */
    const OPTIONS = 'options';

    /** The full set of options (additional and default) which are used for building the layout block */
    const RESOLVED_OPTIONS = 'resolved_options';

    /** The layout item path */
    const PATH = 'path';

    /**
     * @var array
     *
     * Example:
     *  [
     *      'root' => [
     *          'path'             => ['root'],
     *          'block_type'       => 'root',
     *          'options'          => [],
     *          'resolved_options' => []
     *          'property1'        => 'some value'
     *      ],
     *      'header' => [
     *          'path'             => ['root', 'header'],
     *          'block_type'       => 'panel',
     *          'options'          => [],
     *          'property1'        => 'some value',
     *          'property2'        => 123
     *      ],
     *      'menu' => [
     *          'path'             => ['root', 'header', 'menu']
     *          'block_type'       => 'menu',
     *          'options'          => []
     *      ]
     *  ]
     */
    protected $items = [];

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
     * @param string $id The layout item id or alias
     *
     * @return string The layout item id
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
     * @param string $id The layout item id
     *
     * @return bool
     */
    public function hasItem($id)
    {
        $id = $this->resolveItemId($id);

        return isset($this->items[$id]);
    }

    /**
     * Adds a new item to the layout
     *
     * @param string $id        The layout item id
     * @param string $parentId  The parent item id or alias
     * @param string $blockType The block type associated with the layout item
     * @param array  $options   The layout item options
     */
    public function addItem($id, $parentId = null, $blockType = null, array $options = [])
    {
        if (empty($id)) {
            throw new Exception\InvalidArgumentException('The item id must not be empty.');
        }
        if (isset($this->items[$id])) {
            throw new Exception\ItemAlreadyExistsException(
                sprintf(
                    'The "%s" item already exists.'
                    . ' Remove existing item before add the new item with the same id.',
                    $id
                )
            );
        }
        if (empty($parentId)) {
            if (!$this->hierarchy->isEmpty()) {
                throw new Exception\LogicException(
                    sprintf(
                        'The "%s" item cannot be the root item'
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
            $parentId = $this->resolveItemId($parentId);
            $path     = $this->getItemProperty($parentId, self::PATH);
        }
        $path[] = $id;

        $this->hierarchy->add($path, $id);
        $this->items[$id] = [
            self::PATH       => $path,
            self::BLOCK_TYPE => $blockType,
            self::OPTIONS    => $options
        ];
    }

    /**
     * Removes the given item from the layout
     *
     * @param string $id The id of the layout item to be removed
     */
    public function removeItem($id)
    {
        if (empty($id)) {
            throw new Exception\InvalidArgumentException('The item id must not be empty.');
        }
        $id = $this->resolveItemId($id);
        if (!isset($this->items[$id])) {
            throw new Exception\ItemNotFoundException(sprintf('The "%s" item does not exist.', $id));
        }

        $path = $this->items[$id][self::PATH];

        // remove item from hierarchy
        $this->hierarchy->remove($path);
        // remove item
        unset($this->items[$id]);
        $this->aliases->removeById($id);
        // remove all children
        $pathLength    = count($path);
        $pathLastIndex = $pathLength - 1;
        $ids           = array_keys($this->items);
        foreach ($ids as $itemId) {
            $currentPath = $this->getItemProperty($itemId, self::PATH);
            if (count($currentPath) > $pathLength && $currentPath[$pathLastIndex] === $id) {
                unset($this->items[$itemId]);
                $this->aliases->removeById($itemId);
            }
        }
    }

    /**
     * Checks if the layout item has the given additional property
     *
     * @param string $id   The layout item id
     * @param string $name The property name
     *
     * @return boolean
     *
     * @throws Exception\ItemNotFoundException if the layout item does not exist
     */
    public function hasItemProperty($id, $name)
    {
        if (empty($id)) {
            throw new Exception\InvalidArgumentException('The item id must not be empty.');
        }
        $id = $this->resolveItemId($id);
        if (!isset($this->items[$id])) {
            throw new Exception\ItemNotFoundException(sprintf('The "%s" item does not exist.', $id));
        }

        return isset($this->items[$id][$name]) || array_key_exists($name, $this->items[$id]);
    }

    /**
     * Gets a value of an additional property for the layout item
     *
     * @param string $id   The layout item id
     * @param string $name The property name
     *
     * @return mixed
     *
     * @throws Exception\ItemNotFoundException if the layout item does not exist
     * @throws Exception\LogicException if the layout item does not have the requested property
     */
    public function getItemProperty($id, $name)
    {
        if (empty($id)) {
            throw new Exception\InvalidArgumentException('The item id must not be empty.');
        }
        $id = $this->resolveItemId($id);
        if (!isset($this->items[$id])) {
            throw new Exception\ItemNotFoundException(sprintf('The "%s" item does not exist.', $id));
        }

        return $this->items[$id][$name];
    }

    /**
     * Sets a value of an additional property for the layout item
     *
     * @param string $id    The layout item id
     * @param string $name  The property name
     * @param mixed  $value The property value
     *
     * @throws Exception\ItemNotFoundException if the layout item does not exist
     */
    public function setItemProperty($id, $name, $value)
    {
        if (empty($id)) {
            throw new Exception\InvalidArgumentException('The item id must not be empty.');
        }
        $id = $this->resolveItemId($id);
        if (!isset($this->items[$id])) {
            throw new Exception\ItemNotFoundException(sprintf('The "%s" item does not exist.', $id));
        }

        $this->items[$id][$name] = $value;
    }

    /**
     * Creates an alias for the specified layout item
     *
     * @param string $alias A string that can be used to access to the layout item instead of its id
     * @param string $id    The layout item id
     */
    public function addItemAlias($alias, $id)
    {
        if (empty($alias)) {
            throw new Exception\InvalidArgumentException('The item alias must not be empty.');
        }
        if (empty($id)) {
            throw new Exception\InvalidArgumentException('The item id must not be empty.');
        }
        if ($alias === $id) {
            throw new Exception\ItemAlreadyExistsException(
                sprintf(
                    'The "%s" sting cannot be used as an alias for "%s" item'
                    . ' because an alias cannot be equal to the item id.',
                    $alias,
                    $id
                )
            );
        }
        if (isset($this->items[$alias])) {
            throw new Exception\ItemAlreadyExistsException(
                sprintf(
                    'The "%s" sting cannot be used as an alias for "%s" item'
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
     * @param string $alias The layout item alias
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
     * @param string $id The layout item id
     *
     * @return array
     *
     * @throws Exception\ItemNotFoundException if the layout item does not exist
     */
    public function getHierarchy($id)
    {
        return $this->hierarchy->get($this->getItemProperty($id, self::PATH));
    }

    /**
     * Returns an iterator which can be used to get ids of all children of the given item
     * The iteration is performed from parent to child
     *
     * @param string $id The layout item id
     *
     * @return \Iterator
     *
     * @throws Exception\ItemNotFoundException if the layout item does not exist
     */
    public function getHierarchyIterator($id)
    {
        return new \RecursiveIteratorIterator(
            new KeyAsValueRecursiveArrayIterator($this->getHierarchy($id)),
            \RecursiveIteratorIterator::SELF_FIRST
        );
    }
}
