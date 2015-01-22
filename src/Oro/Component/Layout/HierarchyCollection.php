<?php

namespace Oro\Component\Layout;

class HierarchyCollection
{
    /**
     * @var array
     *
     * Example:
     *  [
     *      'root' => [
     *          'header' => [
     *              'logo' => [],
     *              'menu' => [
     *                  'favorites' => [],
     *                  'history'   => []
     *              ]
     *          ],
     *          'body'   => [],
     *          'footer' => [
     *              'links' => []
     *          ]
     *      ]
     *  ]
     */
    protected $hierarchy = [];

    /**
     * Returns the identifier of the root item
     *
     * @return string
     *
     * @throws Exception\LogicException if the root item does not exist
     */
    public function getRootId()
    {
        if (empty($this->hierarchy)) {
            throw new Exception\LogicException('The root item does not exist.');
        }

        reset($this->hierarchy);
        $id = key($this->hierarchy);

        return $id;
    }

    /**
     * @param string[] $path
     *
     * @return array
     */
    public function get(array $path)
    {
        $current = &$this->hierarchy;
        foreach ($path as $childId) {
            if (!isset($current[$childId])) {
                return [];
            }
            $current = &$current[$childId];
        }

        return $current;
    }

    /**
     * @param string[] $parentPath
     * @param string   $id
     *
     * @throws Exception\LogicException if the operation failed
     */
    public function add(array $parentPath, $id)
    {
        $current          = &$this->hierarchy;
        $parentPathLength = count($parentPath);
        for ($i = 0; $i < $parentPathLength; $i++) {
            if (!isset($current[$parentPath[$i]])) {
                if ($i === 0) {
                    throw new Exception\LogicException(
                        sprintf(
                            'Cannot add "%s" item to "%s" because "%s" root item does not exist.',
                            $id,
                            implode('/', $parentPath),
                            $parentPath[$i]
                        )
                    );
                } else {
                    throw new Exception\LogicException(
                        sprintf(
                            'Cannot add "%s" item to "%s" because "%s" item does not have "%s" child.',
                            $id,
                            implode('/', $parentPath),
                            $parentPath[$i - 1],
                            $parentPath[$i]
                        )
                    );
                }
            }
            $current = &$current[$parentPath[$i]];
        }
        if (isset($current[$id])) {
            throw new Exception\LogicException(
                sprintf(
                    'Cannot add "%s" item to "%s" because such item already exists.',
                    $id,
                    implode('/', $parentPath)
                )
            );
        }
        $current[$id] = [];
    }

    /**
     * @param string[] $path
     */
    public function remove(array $path)
    {
        $current    = &$this->hierarchy;
        $pathLength = count($path);
        for ($i = 0; $i < $pathLength; $i++) {
            if (!isset($current[$path[$i]])) {
                break;
            }
            if ($i === $pathLength - 1) {
                unset($current[$path[$i]]);
                break;
            }
            $current = &$current[$path[$i]];
        }
    }

    /**
     * Checks whether the hierarchy is empty
     *
     * @return bool
     */
    public function isEmpty()
    {
        return empty($this->hierarchy);
    }
}
