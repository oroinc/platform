<?php

namespace Oro\Component\Layout;

/**
 * Represents the raw layout configuration and provides methods to modify these data
 */
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
     * @param string $parentId  The parent item id or alias. Set null to add the root item
     * @param string $blockType The name of the block type associated with the layout item
     * @param array  $options   The layout item options
     *
     * @throws Exception\InvalidArgumentException if the id, parent id or block type are empty or invalid
     * @throws Exception\ItemAlreadyExistsException if the layout item with the same id already exists
     * @throws Exception\ItemNotFoundException if the parent layout item does not exist
     * @throws Exception\LogicException if the layout item cannot be added by other reasons
     */
    public function addItem($id, $parentId, $blockType, array $options = [])
    {
        $this->validateItemId($id, true);
        $this->validateBlockType($blockType);
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
        }

        if (empty($parentId)) {
            $path = [];
        } else {
            $parentId = $this->resolveItemId($parentId);
            $path     = $this->getItemProperty($parentId, self::PATH);
        }

        $this->hierarchy->add($path, $id);
        $path[]           = $id;
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
     *
     * @throws Exception\InvalidArgumentException if the id is empty
     * @throws Exception\ItemNotFoundException if the layout item does not exist
     */
    public function removeItem($id)
    {
        $id   = $this->validateAndResolveItemId($id);
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
     * @throws Exception\InvalidArgumentException if the id is empty
     * @throws Exception\ItemNotFoundException if the layout item does not exist
     */
    public function hasItemProperty($id, $name)
    {
        $id = $this->validateAndResolveItemId($id);

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
     * @throws Exception\InvalidArgumentException if the id is empty
     * @throws Exception\ItemNotFoundException if the layout item does not exist
     * @throws Exception\LogicException if the layout item does not have the requested property
     */
    public function getItemProperty($id, $name)
    {
        $id = $this->validateAndResolveItemId($id);
        if (!isset($this->items[$id][$name]) && !array_key_exists($name, $this->items[$id])) {
            throw new Exception\LogicException(
                sprintf('The "%s" item does not have "%s" property.', $id, $name)
            );
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
     * @throws Exception\InvalidArgumentException if the id is empty
     * @throws Exception\ItemNotFoundException if the layout item does not exist
     */
    public function setItemProperty($id, $name, $value)
    {
        $id = $this->validateAndResolveItemId($id);

        $this->items[$id][$name] = $value;
    }

    /**
     * Checks if the given layout item alias exists
     *
     * @param string $alias The layout item alias
     *
     * @return bool
     */
    public function hasItemAlias($alias)
    {
        return $this->aliases->has($alias);
    }

    /**
     * Creates an alias for the specified layout item
     *
     * @param string $alias A string that can be used to access to the layout item instead of its id
     * @param string $id    The layout item id
     *
     * @throws Exception\InvalidArgumentException if the alias or id are empty or invalid
     * @throws Exception\ItemNotFoundException if the layout item with the given id does not exist
     * @throws Exception\AliasAlreadyExistsException if the alias is used for another layout item
     * @throws Exception\LogicException if the alias cannot be added by other reasons
     */
    public function addItemAlias($alias, $id)
    {
        $this->validateItemAlias($alias, true);
        $this->validateItemId($id, true);
        // perform additional validations
        if ($alias === $id) {
            throw new Exception\LogicException(
                sprintf(
                    'The "%s" sting cannot be used as an alias for "%s" item'
                    . ' because an alias cannot be equal to the item id.',
                    $alias,
                    $id
                )
            );
        }
        if (isset($this->items[$alias])) {
            throw new Exception\LogicException(
                sprintf(
                    'The "%s" sting cannot be used as an alias for "%s" item'
                    . ' because another item with the same id exists.',
                    $alias,
                    $id
                )
            );
        }
        if (!isset($this->items[$this->resolveItemId($id)])) {
            throw new Exception\ItemNotFoundException(sprintf('The "%s" item does not exist.', $id));
        }

        $this->aliases->add($alias, $id);
    }

    /**
     * Removes the layout item alias
     *
     * @param string $alias The layout item alias
     *
     * @throws Exception\InvalidArgumentException if the alias is empty
     * @throws Exception\AliasNotFoundException if the alias does not exist
     */
    public function removeItemAlias($alias)
    {
        $this->validateItemAlias($alias);
        if (!$this->aliases->has($alias)) {
            throw new Exception\AliasNotFoundException(sprintf('The "%s" item alias does not exist.', $alias));
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
     * @throws Exception\InvalidArgumentException if the id is empty
     * @throws Exception\ItemNotFoundException if the layout item does not exist
     */
    public function getHierarchy($id)
    {
        $path = $this->getItemProperty($id, self::PATH);

        return $this->hierarchy->get($path);
    }

    /**
     * Returns an iterator which can be used to get ids of all children of the given item
     * The iteration is performed from parent to child
     *
     * @param string $id The layout item id
     *
     * @return \Iterator
     *
     * @throws Exception\InvalidArgumentException if the id is empty
     * @throws Exception\ItemNotFoundException if the layout item does not exist
     */
    public function getHierarchyIterator($id)
    {
        return new \RecursiveIteratorIterator(
            new KeyAsValueRecursiveArrayIterator($this->getHierarchy($id)),
            \RecursiveIteratorIterator::SELF_FIRST
        );
    }

    /**
     * Checks whether the given string is a valid item identifier
     *
     * A identifier is accepted if it
     *   * starts with a letter
     *   * contains only letters, digits, numbers, underscores ("_"),
     *     hyphens ("-") and colons (":")
     *
     * @param string $id The layout item id to be tested
     *
     * @return bool
     */
    protected function isValidId($id)
    {
        return preg_match('/^[a-z][a-z0-9_\-:]*$/iD', $id);
    }

    /**
     * Checks if the given value can be used as the layout item id
     *
     * @param string $id        The layout item id
     * @param bool   $fullCheck Determines whether all validation rules should be applied
     *                          or it is required to validate for empty value only
     *
     * @throws Exception\InvalidArgumentException if the id is not valid
     */
    protected function validateItemId($id, $fullCheck = false)
    {
        if (empty($id)) {
            throw new Exception\InvalidArgumentException('The item id must not be empty.');
        }
        if ($fullCheck) {
            if (!is_string($id)) {
                throw new Exception\UnexpectedTypeException($id, 'string', 'id');
            }
            if (!$this->isValidId($id)) {
                throw new Exception\InvalidArgumentException(
                    sprintf(
                        'The "%s" string cannot be used as the item id because it contains illegal characters. '
                        . 'The valid item id should start with a letter and only contain '
                        . 'letters, numbers, underscores ("_"), hyphens ("-") and colons (":").',
                        $id
                    )
                );
            }
        }
    }

    /**
     * Checks the layout item id for empty and returns real id
     * Also this method raises an exception if the layout item does not exist
     *
     * @param string $id The layout item id
     *
     * @return string The resolved item id
     *
     * @throws Exception\InvalidArgumentException if the id is empty
     * @throws Exception\ItemNotFoundException if the layout item does not exist
     */
    protected function validateAndResolveItemId($id)
    {
        $this->validateItemId($id);
        $id = $this->resolveItemId($id);
        if (!isset($this->items[$id])) {
            throw new Exception\ItemNotFoundException(sprintf('The "%s" item does not exist.', $id));
        }

        return $id;
    }

    /**
     * Checks if the given value can be used as an alias for the layout item
     *
     * @param string $alias     The layout item alias
     * @param bool   $fullCheck Determines whether all validation rules should be applied
     *                          or it is required to validate for empty value only
     *
     * @throws Exception\InvalidArgumentException if the alias is not valid
     */
    protected function validateItemAlias($alias, $fullCheck = false)
    {
        if (empty($alias)) {
            throw new Exception\InvalidArgumentException('The item alias must not be empty.');
        }
        if ($fullCheck) {
            if (!is_string($alias)) {
                throw new Exception\UnexpectedTypeException($alias, 'string', 'alias');
            }
            if (!$this->isValidId($alias)) {
                throw new Exception\InvalidArgumentException(
                    sprintf(
                        'The "%s" string cannot be used as the item alias because it contains illegal characters. '
                        . 'The valid alias should start with a letter and only contain '
                        . 'letters, numbers, underscores ("_"), hyphens ("-") and colons (":").',
                        $alias
                    )
                );
            }
        }
    }

    /**
     * Checks if the given value can be used as the block type name
     *
     * @param string $blockType The name of the block type
     *
     * @throws Exception\InvalidArgumentException if the block type name is not valid
     */
    protected function validateBlockType($blockType)
    {
        if (empty($blockType)) {
            throw new Exception\InvalidArgumentException('The block type name must not be empty.');
        }
        if (!is_string($blockType)) {
            throw new Exception\UnexpectedTypeException($blockType, 'string', 'blockType');
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
