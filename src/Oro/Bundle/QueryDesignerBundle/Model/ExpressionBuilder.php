<?php

namespace Oro\Bundle\QueryDesignerBundle\Model;

use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\QueryBuilder;

use Oro\Bundle\FilterBundle\Filter\FilterUtility;

class ExpressionBuilder
{
    /** @var GroupNode */
    protected $groupNode;

    /** @var GroupNode */
    protected $currentGroupNode;

    /**
     * @param string $condition
     */
    public function beginGroup($condition)
    {
        $groupNode = new GroupNode($condition);
        if ($this->currentGroupNode) {
            $this->currentGroupNode->addNode($groupNode);
            $this->currentGroupNode = $groupNode;
        } else {
            $this->groupNode = $groupNode;
            $this->currentGroupNode = $this->groupNode;
        }
    }

    /**
     * @param Restriction $restriction
     */
    public function addRestriction(Restriction $restriction)
    {
        if (!$this->groupNode) {
            $this->groupNode = new GroupNode(FilterUtility::CONDITION_AND);
            $this->currentGroupNode = $this->groupNode;
        }

        $this->currentGroupNode->addNode($restriction);
    }

    public function endGroup()
    {
        $this->currentGroupNode = $this->currentGroupNode->getParent();
    }

    /**
     * @param QueryBuilder $qb
     */
    public function applyRestrictions(QueryBuilder $qb)
    {
        if (!$this->groupNode) {
            return;
        }

        $expr = $this->resolveGroupNode($this->groupNode);
        if ($this->groupNode->isComputed()) {
            $qb->andHaving($expr);
        } else {
            $qb->andWhere($expr);
        }
    }

    /**
     * @param GroupNode $gNode
     *
     * @return mixed Expr
     */
    protected function resolveGroupNode(GroupNode $gNode)
    {
        $restrictions = [];
        foreach ($gNode->getChildren() as $node) {
            if ($node instanceof Restriction) {
                $restrictions[] = $node;
            } else {
                $expr = $this->resolveGroupNode($node);
                $restrictions[] = new Restriction($expr, $node->getCondition(), $node->isComputed());
            }
        }

        return $this->createExprFromRestrictions($restrictions);
    }

    /**
     * @param Restriction[] $restrictions
     *
     * @return mixed Expr
     */
    protected function createExprFromRestrictions(array $restrictions)
    {
        return array_reduce(
            $restrictions,
            function ($expr = null, Restriction $restriction) {
                if ($expr === null) {
                    return $restriction->getRestriction();
                }

                if ($restriction->getCondition() === FilterUtility::CONDITION_OR) {
                    if ($expr instanceof Expr\Orx) {
                        $expr->add($restriction->getRestriction());
                    } else {
                        $expr = new Expr\Orx([$expr, $restriction->getRestriction()]);
                    }
                } else {
                    if ($expr instanceof Expr\Andx) {
                        $expr->add($restriction->getRestriction());
                    } else {
                        $expr = new Expr\Andx([$expr, $restriction->getRestriction()]);
                    }
                }

                return $expr;
            }
        );
    }
}
