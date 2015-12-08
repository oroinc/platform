<?php

namespace Oro\Bundle\QueryDesignerBundle\Model;

use Oro\Component\PhpUtils\ArrayUtil;

class GroupNode
{
    const TYPE_COMPUTED = 'computed';
    const TYPE_UNCOMPUTED = 'uncomputed';
    const TYPE_MIXED = 'mixed';

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
     *
     * @return $this
     */
    public function addNode($node)
    {
        $this->nodes[] = $node;
        if ($node instanceof GroupNode) {
            $node->setParent($this);
        }

        return $this;
    }

    /**
     * @param GroupNode $node
     *
     * @return $this
     */
    public function setParent(GroupNode $node)
    {
        $this->parentNode = $node;

        return $this;
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
     * @return string
     */
    public function getType()
    {
        $mixed = ArrayUtil::some(
            function ($node) {
                if ($node instanceof GroupNode) {
                    return $node->getType() === GroupNode::TYPE_MIXED;
                }

                return false;
            },
            $this->nodes
        );

        if ($mixed) {
            return GroupNode::TYPE_MIXED;
        }

        $computed = ArrayUtil::some(
            function ($node) {
                if ($node instanceof GroupNode) {
                    return $node->getType() === GroupNode::TYPE_COMPUTED;
                }

                return $node->isComputed();
            },
            $this->nodes
        );

        $unComputed = ArrayUtil::some(
            function ($node) {
                if ($node instanceof GroupNode) {
                    return $node->getType() === GroupNode::TYPE_UNCOMPUTED;
                }

                return !$node->isComputed();
            },
            $this->nodes
        );

        if ($computed && $unComputed) {
            return static::TYPE_MIXED;
        }

        return $computed ? static::TYPE_COMPUTED : static::TYPE_UNCOMPUTED;
    }
}
