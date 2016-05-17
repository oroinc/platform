<?php

namespace Oro\Component\Layout;

class AliasCollection
{
    /**
     * @var string[]
     *
     * Example:
     *  [
     *      'my_header'   => 'header',
     *      'my_footer'   => 'footer',
     *      'page_header' => 'header', // second alias for the header item
     *      'some_header' => 'my_header' // one more alias for the header item
     *  ]
     */
    protected $aliases = [];

    /**
     * @var array
     *
     * Example:
     *  [
     *      'header' => ['my_header', 'page_header', 'some_header'],
     *      'footer' => ['my_footer'],
     *  ]
     */
    protected $ids = [];

    /**
     * Checks if the given alias is used for some item
     *
     * @param string $alias The item alias
     *
     * @return bool
     */
    public function has($alias)
    {
        return isset($this->aliases[$alias]);
    }

    /**
     * Returns the identifier of the item which has the given alias
     *
     * @param string $alias The item alias
     *
     * @return string|null The item identifier or null if no one item has the given alias
     */
    public function getId($alias)
    {
        if (!isset($this->aliases[$alias])) {
            return null;
        }

        $id = $this->aliases[$alias];
        while (isset($this->aliases[$id])) {
            $id = $this->aliases[$id];
        }

        return $id;
    }

    /**
     * Returns a list of all aliases registered for the given item
     *
     * @param string $id The item id
     *
     * @return string[] The list of all aliases for the item with the given id
     */
    public function getAliases($id)
    {
        return isset($this->ids[$id])
            ? $this->ids[$id]
            : [];
    }

    /**
     * Registers an alias for the specified identifier
     *
     * @param string $alias A string that can be used as an alias for the item id
     * @param string $id    The item identifier or already registered item alias
     *
     * @return self
     *
     * @throws Exception\AliasAlreadyExistsException if the alias is used for another item
     */
    public function add($alias, $id)
    {
        if (isset($this->aliases[$alias])) {
            if ($this->aliases[$alias] === $id) {
                // exactly the same alias for the specified item is already exists
                return $this;
            }

            throw new Exception\AliasAlreadyExistsException(
                sprintf(
                    'The "%s" sting cannot be used as an alias for "%s" item'
                    . ' because it is already used for "%s" item.',
                    $alias,
                    $id,
                    $this->aliases[$alias]
                )
            );
        }

        $this->aliases[$alias] = $id;

        $id               = $this->getId($alias);
        $this->ids[$id][] = $alias;

        return $this;
    }

    /**
     * Removes the alias
     *
     * @param string $alias The item alias
     *
     * @return self
     */
    public function remove($alias)
    {
        $id = $this->getId($alias);
        if ($id) {
            unset($this->aliases[$alias]);
            $aliases = &$this->ids[$id];
            unset($aliases[array_search($alias, $aliases, true)]);
            if (!empty($aliases)) {
                $this->removeDependedAliases($alias, $id);
            } else {
                unset($this->ids[$id]);
            }
        }

        return $this;
    }

    /**
     * Removes all aliases for the specified item
     *
     * @param string $id The identifier of item which aliases should be removed
     *
     * @return self
     */
    public function removeById($id)
    {
        if (isset($this->ids[$id])) {
            foreach ($this->ids[$id] as $alias) {
                unset($this->aliases[$alias]);
            }
            unset($this->ids[$id]);
        }

        return $this;
    }

    /**
     * Checks whether at least one item has an alias
     *
     * @return bool
     */
    public function isEmpty()
    {
        return empty($this->aliases);
    }

    /**
     * Removes all data from this collection
     */
    public function clear()
    {
        $this->aliases = [];
        $this->ids     = [];
    }

    /**
     * Removes all depended aliases
     * For example if "alias3" is removed in alias chain like "alias1 -> alias2 -> alias3 -> id"
     * than both "alias1" and "alias2" are removed as well
     *
     * @param string $alias
     * @param string $id
     */
    protected function removeDependedAliases($alias, $id)
    {
        foreach ($this->ids[$id] as $otherAlias) {
            if (isset($this->aliases[$otherAlias]) && $this->aliases[$otherAlias] === $alias) {
                unset($this->aliases[$otherAlias]);
                $aliases = &$this->ids[$id];
                unset($aliases[array_search($otherAlias, $aliases, true)]);
                if (!empty($aliases)) {
                    $this->removeDependedAliases($otherAlias, $id);
                } else {
                    unset($this->ids[$id]);
                }
            }
        }
    }
}
