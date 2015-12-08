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
        if (!$this->currentGroupNode && $this->groupNode) {
            $this->groupNode = $groupNode->addNode($this->groupNode);
            $this->currentGroupNode = $this->groupNode;
        }

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

        list($uncomputedExpr, $computedExpr) = $this->resolveGroupNode($this->groupNode);
        if ($computedExpr) {
            $qb->andHaving($computedExpr);
        }
        if ($uncomputedExpr) {
            $qb->andWhere($uncomputedExpr);
        }
    }

    /**
     * @param GroupNode $gNode
     *
     * @return mixed Expr
     */
    protected function resolveGroupNode(GroupNode $gNode)
    {
        $uncomputedRestrictions = [];
        $computedRestrictions = [];

        foreach ($gNode->getChildren() as $node) {
            if ($node instanceof Restriction) {
                if ($node->isComputed()) {
                    $computedRestrictions[] = $node;
                } else {
                    $uncomputedRestrictions[] = $node;
                }
            } else {
                list($uncomputedExpr, $computedExpr) = $this->resolveGroupNode($node);
                if ($uncomputedExpr) {
                    $uncomputedRestrictions[] = new Restriction($uncomputedExpr, $node->getCondition(), $node->isComputed());
                }
                if ($computedExpr) {
                    $computedRestrictions[] = new Restriction($computedExpr, $node->getCondition(), $node->isComputed());
                }
            }
        }

        return [
            $this->createExprFromRestrictions($uncomputedRestrictions),
            $this->createExprFromRestrictions($computedRestrictions),
        ];
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
