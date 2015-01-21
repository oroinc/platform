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
     * @param string[] $path
     * @param string   $id
     *
     * @throws Exception\LogicException if the operation failed
     */
    public function add(array $path, $id)
    {
        $current    = &$this->hierarchy;
        $pathLength = count($path);
        for ($i = 0; $i < $pathLength - 1; $i++) {
            if (!isset($current[$path[$i]])) {
                if ($i === 0) {
                    throw new Exception\LogicException(
                        sprintf(
                            'Cannot add "%s" item to "%s" because "%s" root item does not exist.',
                            $id,
                            implode($path),
                            $path[$i]
                        )
                    );
                } else {
                    throw new Exception\LogicException(
                        sprintf(
                            'Cannot add "%s" item to "%s" because "%s" item has no "%s" child.',
                            $id,
                            implode($path),
                            $path[$i - 1],
                            $path[$i]
                        )
                    );
                }
            }
            $current = &$current[$path[$i]];
        }
        if (isset($current[$id])) {
            throw new Exception\LogicException(
                sprintf(
                    'Cannot add "%s" item to "%s" because it is already exist.',
                    $id,
                    implode($path)
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
