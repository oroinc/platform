<?php

namespace Oro\Bundle\QueryDesignerBundle\Model;

use LogicException;
use Oro\Component\PhpUtils\ArrayUtil;

class GroupNode
{
    /** @var string*/
    protected $condition;

    /** @var GroupNode[]|Restriction[] */
    protected $nodes = [];

    /** @var GroupNode|null */
    protected $parentNode;

    /**
     * @param string $condition
     */
    public function __construct($condition)
    {
        $this->condition = $condition;
    }

    /**
     * @param GroupNode|Restriction $node
     */
    public function addNode($node)
    {
        $this->nodes[] = $node;
        if ($node instanceof GroupNode) {
            $node->setParent($this);
        }
    }

    /**
     * @param GroupNode $node
     */
    public function setParent(GroupNode $node)
    {
        $this->parentNode = $node;
    }

    /**
     * @return GroupNode|null
     */
    public function getParent()
    {
        return $this->parentNode;
    }

    /**
     * @param GroupNode[]|Restriction[] $node
     */
    public function getChildren()
    {
        return $this->nodes;
    }

    /**
     * @return string
     */
    public function getCondition()
    {
        return $this->condition;
    }

    /**
     * @return bool
     *
     * @throws LogicException If computed restrictions are mixed with uncomputed
     */
    public function isComputed()
    {
        $computed = ArrayUtil::some(
            function ($node) {
                return $node->isComputed();
            },
            $this->nodes
        );

        $unComputed = ArrayUtil::some(
            function ($node) {
                return !$node->isComputed();
            },
            $this->nodes
        );

        if ($computed && $unComputed) {
            throw new LogicException('Mixing ofcomputed nodes with uncomputed is not implemented');
        }

        return $computed;
    }
}
