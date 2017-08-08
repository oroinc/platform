<?php

namespace Oro\Component\Layout;

/**
 * Represents the raw layout configuration and provides methods to modify these data
 *
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class RawLayout
{
    /** The block type */
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

    /** @var array */
    protected $blockThemes = [];

    /** @var array */
    protected $formThemes = [];

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
    public function getRootId()
    {
        return $this->hierarchy->getRootId();
    }

    /**
     * Returns the id of the parent layout item
     *
     * @param string $id The id or alias of the layout item
     *
     * @return string|null The id of the parent layout item or null if the given item is the root
     */
    public function getParentId($id)
    {
        $path = $this->getProperty($id, self::PATH);
        array_pop($path);

        return empty($path)
            ? null // the given item is the root
            : array_pop($path);
    }

    /**
     * Returns real id of the layout item
     *
     * @param string $id The id or alias of the layout item
     *
     * @return string The layout item id
     */
    public function resolveId($id)
    {
        return $this->aliases->has($id)
            ? $this->aliases->getId($id)
            : $id;
    }

    /**
     * Checks if the layout item with the given id exists
     *
     * @param string $id The id or alias of the layout item
     *
     * @return bool
     */
    public function has($id)
    {
        $id = $this->resolveId($id);

        return isset($this->items[$id]);
    }

    /**
     * Adds a new item to the layout
     *
     * @param string      $id        The layout item id
     * @param string      $parentId  The id or alias of parent item. Set null to add the root item
     * @param mixed       $blockType The block type associated with the layout item
     * @param array       $options   The layout item options
     * @param string|null $siblingId The id or alias of an item which should be nearest neighbor
     * @param bool        $prepend   Determines whether the moving item should be located before or after
     *                               the specified sibling item
     *
     * @throws Exception\InvalidArgumentException if the id or parent id are empty or invalid
     * @throws Exception\ItemAlreadyExistsException if the layout item with the same id already exists
     * @throws Exception\ItemNotFoundException if the parent layout item does not exist
     * @throws Exception\LogicException if the layout item cannot be added by other reasons
     *
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function add(
        $id,
        $parentId,
        $blockType,
        array $options = [],
        $siblingId = null,
        $prepend = null
    ) {
        $this->validateId($id, true);
        if (isset($this->items[$id])) {
            throw new Exception\ItemAlreadyExistsException(
                sprintf(
                    'The "%s" item already exists.'
                    . ' Remove existing item before add the new item with the same id.',
                    $id
                )
            );
        }
        if (!$parentId && !$this->hierarchy->isEmpty()) {
            throw new Exception\LogicException(
                sprintf(
                    'The "%s" item cannot be the root item'
                    . ' because another root item ("%s") already exists.',
                    $id,
                    $this->hierarchy->getRootId()
                )
            );
        }
        if ($parentId) {
            $parentId = $this->validateAndResolveId($parentId);
        }
        if ($siblingId) {
            $siblingId = $this->resolveId($siblingId);
            if (!isset($this->items[$siblingId])) {
                throw new Exception\ItemNotFoundException(sprintf('The "%s" sibling item does not exist.', $siblingId));
            }
            if ($parentId && $siblingId === $parentId) {
                throw new Exception\LogicException('The sibling item cannot be the same as the parent item.');
            }
        }

        $path = $parentId
            ? $this->getProperty($parentId, self::PATH, true)
            : [];
        $this->hierarchy->add($path, $id, $siblingId, $prepend);
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
    public function remove($id)
    {
        $id   = $this->validateAndResolveId($id);
        $path = $this->items[$id][self::PATH];

        // remove item from hierarchy
        $this->hierarchy->remove($path);
        // remove item
        unset($this->items[$id]);
        $this->aliases->removeById($id);
        unset($this->blockThemes[$id]);
        // remove all children
        $pathLength    = count($path);
        $pathLastIndex = $pathLength - 1;
        $ids           = array_keys($this->items);
        foreach ($ids as $itemId) {
            $currentPath = $this->items[$itemId][self::PATH];
            if (count($currentPath) > $pathLength && $currentPath[$pathLastIndex] === $id) {
                unset($this->items[$itemId]);
                $this->aliases->removeById($itemId);
                unset($this->blockThemes[$itemId]);
            }
        }
    }

    /**
     * Moves the given item to another location
     *
     * @param string      $id        The id or alias of the layout item to be moved
     * @param string|null $parentId  The id or alias of a parent item the specified item is moved to
     *                               If this parameter is null only the order of the item is changed
     * @param string|null $siblingId The id or alias of an item which should be nearest neighbor
     * @param bool|null   $prepend   Determines whether the moving item should be located before or after
     *                               the specified sibling item
     *
     * @throws Exception\InvalidArgumentException if the id is empty
     * @throws Exception\ItemNotFoundException if the layout item, parent item or sibling item does not exist
     * @throws Exception\LogicException if the layout item cannot be moved by other reasons
     *
     * @SuppressWarnings(PHPMD.NPathComplexity)
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function move($id, $parentId = null, $siblingId = null, $prepend = null)
    {
        $id = $this->validateAndResolveId($id);
        if ($parentId) {
            $parentId = $this->resolveId($parentId);
            if (!isset($this->items[$parentId])) {
                throw new Exception\ItemNotFoundException(sprintf('The "%s" parent item does not exist.', $parentId));
            }
            if ($parentId === $id) {
                throw new Exception\LogicException('The parent item cannot be the same as the moving item.');
            }
        }
        if ($siblingId) {
            $siblingId = $this->resolveId($siblingId);
            if (!isset($this->items[$siblingId])) {
                throw new Exception\ItemNotFoundException(sprintf('The "%s" sibling item does not exist.', $siblingId));
            }
            if ($siblingId === $id) {
                throw new Exception\LogicException('The sibling item cannot be the same as the moving item.');
            }
        }
        if (!$parentId && !$siblingId) {
            throw new Exception\LogicException('At least one parent or sibling item must be specified.');
        }
        $path = $this->items[$id][self::PATH];
        if (!$parentId) {
            $parentPath = array_slice($path, 0, -1);
        } else {
            if ($siblingId && $siblingId === $parentId) {
                throw new Exception\LogicException('The sibling item cannot be the same as the parent item.');
            }
            $parentPath = $this->items[$parentId][self::PATH];
            if (strpos(implode('/', $parentPath) . '/', implode('/', $path) . '/') === 0) {
                throw new Exception\LogicException(
                    sprintf(
                        'The parent item (path: %s) cannot be a child of the moving item (path: %s).',
                        implode('/', $parentPath),
                        implode('/', $path)
                    )
                );
            }
        }
        // update hierarchy
        $hierarchy = $this->hierarchy->get($path);
        $this->hierarchy->remove($path);
        $this->hierarchy->add($parentPath, $id, $siblingId, $prepend, $hierarchy);
        if ($parentId) {
            // build the new path
            $newPath   = $parentPath;
            $newPath[] = $id;
            // update the path of the moving item
            $this->items[$id][self::PATH] = $newPath;
            // update the path for all children
            $prevPathLength = count($path);
            $iterator       = $this->getHierarchyIterator($id);
            foreach ($iterator as $childId) {
                $this->items[$childId][self::PATH] = array_merge(
                    $newPath,
                    array_slice($this->items[$childId][self::PATH], $prevPathLength)
                );
            }
        }
    }

    /**
     * Checks if the layout item has the given additional property
     *
     * @param string $id           The id or alias of the layout item
     * @param string $name         The property name
     * @param bool   $directAccess Indicated whether the item id validation should be skipped.
     *                             This flag can be used to increase performance of get operation,
     *                             but use it carefully and only when you absolutely sure that
     *                             the value passed as the item id is not an alias
     *
     * @return bool
     *
     * @throws Exception\InvalidArgumentException if the id is empty
     * @throws Exception\ItemNotFoundException if the layout item does not exist
     */
    public function hasProperty($id, $name, $directAccess = false)
    {
        if (!$directAccess) {
            $id = $this->validateAndResolveId($id);
        }

        return isset($this->items[$id][$name]) || array_key_exists($name, $this->items[$id]);
    }

    /**
     * Gets a value of an additional property for the layout item
     *
     * @param string $id           The id or alias of the layout item
     * @param string $name         The property name
     * @param bool   $directAccess Indicated whether the item id and property name validation should be skipped.
     *                             This flag can be used to increase performance of get operation,
     *                             but use it carefully and only when you absolutely sure that:
     *                             * the item with the specified id exists
     *                             * the value passed as the item id is not an alias
     *                             * the property with the specified name exists
     *
     * @return mixed
     *
     * @throws Exception\InvalidArgumentException if the id is empty
     * @throws Exception\ItemNotFoundException if the layout item does not exist
     * @throws Exception\LogicException if the layout item does not have the requested property
     */
    public function getProperty($id, $name, $directAccess = false)
    {
        if ($directAccess) {
            if (!isset($this->items[$id][$name])) {
                return null;
            }

            return $this->items[$id][$name];
        }

        $id = $this->validateAndResolveId($id);
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
     * @param string $id    The id or alias of the layout item
     * @param string $name  The property name
     * @param mixed  $value The property value
     *
     * @throws Exception\InvalidArgumentException if the id is empty
     * @throws Exception\ItemNotFoundException if the layout item does not exist
     */
    public function setProperty($id, $name, $value)
    {
        $id = $this->validateAndResolveId($id);

        $this->items[$id][$name] = $value;
    }

    /**
     * Checks if the given layout item alias exists
     *
     * @param string $alias The layout item alias
     *
     * @return bool
     */
    public function hasAlias($alias)
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
    public function addAlias($alias, $id)
    {
        $this->validateAlias($alias, true);
        $this->validateId($id, true);
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
        if (!isset($this->items[$this->resolveId($id)])) {
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
    public function removeAlias($alias)
    {
        $this->validateAlias($alias);
        if (!$this->aliases->has($alias)) {
            throw new Exception\AliasNotFoundException(sprintf('The "%s" item alias does not exist.', $alias));
        }

        $this->aliases->remove($alias);
    }

    /**
     * Returns a list of all aliases registered for the given item
     *
     * @param string $id The layout item id
     *
     * @return string[] The list of all aliases for the layout item with the given id
     */
    public function getAliases($id)
    {
        return $this->aliases->getAliases($id);
    }

    /**
     * Returns all registered themes to be used for rendering layout items
     *
     * Example of returned data:
     *  [
     *      'root_item_id' => [
     *          'MyBundle:Layout:my_theme.html.twig',
     *          'AcmeBundle:Layout:some_theme.html.twig'
     *      ],
     *      'item_id1' => [
     *          'MyBundle:Layout:custom_item.html.twig'
     *      ]
     *  ]
     *
     * @return array
     */
    public function getBlockThemes()
    {
        return $this->blockThemes;
    }

    /**
     * Sets the theme(s) to be used for rendering the layout item and its children
     *
     * @param string          $id     The id of the layout item to assign the theme(s) to
     * @param string|string[] $themes The theme(s). For example 'MyBundle:Layout:my_theme.html.twig'
     *
     * @return self
     */
    public function setBlockTheme($id, $themes)
    {
        $id = $this->validateAndResolveId($id);
        if (empty($themes)) {
            throw new Exception\InvalidArgumentException('The theme must not be empty.');
        }
        if (!is_string($themes) && !is_array($themes)) {
            throw new Exception\UnexpectedTypeException($themes, 'string or array of strings', 'themes');
        }
        if (!isset($this->blockThemes[$id])) {
            $this->blockThemes[$id] = (array)$themes;
        } else {
            $this->blockThemes[$id] = array_merge($this->blockThemes[$id], (array)$themes);
        }
    }

    /**
     * Returns all registered themes to be used for rendering forms
     *
     * Example of returned data:
     *  [
     *      'MyBundle:Layout:div_form_layout.html.twig',
     *      'AcmeBundle:Layout:div_form_layout.html.twig'
     *  ]
     *
     * @return array
     */
    public function getFormThemes()
    {
        return $this->formThemes;
    }

    /**
     * Sets the theme(s) to be used for rendering the layout item and its children
     *
     * @param string|string[] $themes The theme(s). For example 'MyBundle:Layout:my_theme.html.twig'
     *
     * @return self
     */
    public function setFormTheme($themes)
    {
        if (empty($themes)) {
            throw new Exception\InvalidArgumentException('The theme must not be empty.');
        }
        if (!is_string($themes) && !is_array($themes)) {
            throw new Exception\UnexpectedTypeException($themes, 'string or array of strings', 'themes');
        }

        $this->formThemes = array_merge($this->formThemes, (array)$themes);
    }

    /**
     * Returns the layout items hierarchy from the given path
     *
     * @param string $id The id or alias of the layout item
     *
     * @return array
     *
     * @throws Exception\InvalidArgumentException if the id is empty
     * @throws Exception\ItemNotFoundException if the layout item does not exist
     */
    public function getHierarchy($id)
    {
        $path = $this->getProperty($id, self::PATH);

        return $this->hierarchy->get($path);
    }

    /**
     * Returns an iterator which can be used to get ids of all children of the given item
     * The iteration is performed from parent to child
     *
     * @param string $id The id or alias of the layout item
     *
     * @return HierarchyIterator
     *
     * @throws Exception\InvalidArgumentException if the id is empty
     * @throws Exception\ItemNotFoundException if the layout item does not exist
     */
    public function getHierarchyIterator($id)
    {
        $id       = $this->validateAndResolveId($id);
        $children = $this->hierarchy->get($this->getProperty($id, self::PATH, true));

        return new HierarchyIterator($id, $children);
    }

    /**
     * Checks whether this storage has data
     *
     * @return bool
     */
    public function isEmpty()
    {
        return empty($this->items);
    }

    /**
     * Removes all data from this storage
     */
    public function clear()
    {
        $this->items = [];
        $this->aliases->clear();
        $this->hierarchy->clear();
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
    protected function validateId($id, $fullCheck = false)
    {
        if (!$id) {
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
    protected function validateAndResolveId($id)
    {
        $this->validateId($id);
        $id = $this->resolveId($id);
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
    protected function validateAlias($alias, $fullCheck = false)
    {
        if (!$alias) {
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
}
