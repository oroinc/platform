<?php

namespace Oro\Bundle\QueryDesignerBundle\Model;

use Oro\Component\PhpUtils\ArrayUtil;

class GroupNode
{
    const TYPE_COMPUTED   = 'computed';
    const TYPE_UNCOMPUTED = 'uncomputed';
    const TYPE_MIXED      = 'mixed';

    /** @var string */
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
     * @return GroupNode[]|Restriction[]
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
        $mixed = $this->typedNodesExists(GroupNode::TYPE_MIXED);

        if ($mixed) {
            return GroupNode::TYPE_MIXED;
        }

        $computed   = $this->typedNodesExists(GroupNode::TYPE_COMPUTED);
        $unComputed = $this->typedNodesExists(GroupNode::TYPE_UNCOMPUTED);

        if ($computed && $unComputed) {
            return static::TYPE_MIXED;
        }

        return $computed ? static::TYPE_COMPUTED : static::TYPE_UNCOMPUTED;
    }

    /**
     * @param $type
     *
     * @return bool
     */
    protected function typedNodesExists($type)
    {
        return ArrayUtil::some(
            function ($node) use ($type) {
                $exists = false;

                if ($node instanceof GroupNode) {
                    $exists = $node->getType() === $type;
                } else {
                    switch ($type) {
                        case GroupNode::TYPE_COMPUTED:
                            $exists = $node->isComputed();
                            break;
                        case GroupNode::TYPE_UNCOMPUTED:
                            $exists = !$node->isComputed();
                    }
                }

                return $exists;
            },
            $this->nodes
        );
    }
}
