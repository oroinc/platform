<?php

namespace Oro\Bundle\QueryDesignerBundle\Model;

use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\FilterBundle\Filter\FilterUtility;
use Oro\Bundle\QueryDesignerBundle\Validator\Constraints\GroupNodeConditions;
use Symfony\Component\Validator\ConstraintViolationInterface;
use Symfony\Component\Validator\Validation;

/**
 * Applies an expression to ORM query.
 */
class ExpressionBuilder
{
    /** @var GroupNode|null */
    private $groupNode;

    /** @var GroupNode|null */
    private $currentGroupNode;

    public function beginGroup(string $condition): void
    {
        $groupNode = new GroupNode($condition);
        if (null !== $this->currentGroupNode) {
            $this->currentGroupNode->addNode($groupNode);
            $this->currentGroupNode = $groupNode;
        } elseif (null !== $this->groupNode) {
            $this->groupNode->addNode($groupNode);
            $this->currentGroupNode = $groupNode;
        } else {
            $this->groupNode = $groupNode;
            $this->currentGroupNode = $groupNode;
        }
    }

    public function endGroup(): void
    {
        $this->currentGroupNode = $this->currentGroupNode->getParent();
    }

    public function addRestriction(Restriction $restriction): void
    {
        if (null === $this->groupNode) {
            $this->groupNode = new GroupNode(FilterUtility::CONDITION_AND);
            $this->currentGroupNode = $this->groupNode;
        }

        $this->currentGroupNode->addNode($restriction);
    }

    public function applyRestrictions(QueryBuilder $qb): void
    {
        if (null === $this->groupNode) {
            return;
        }

        $violation = $this->validate();
        if (null !== $violation) {
            throw new \LogicException($violation->getMessage());
        }

        [$uncomputedExpr, $computedExpr] = $this->resolveGroupNode($this->groupNode);
        if ($computedExpr) {
            $qb->andHaving($computedExpr);
        }
        if ($uncomputedExpr) {
            $qb->andWhere($uncomputedExpr);
        }
    }

    private function validate(): ?ConstraintViolationInterface
    {
        $violations = Validation::createValidator()->validate($this->groupNode, new GroupNodeConditions());

        return $violations->count() > 0
            ? $violations[0]
            : null;
    }

    /**
     * @param GroupNode $gNode
     *
     * @return array [uncomputed expression, computed expression]
     */
    private function resolveGroupNode(GroupNode $gNode): array
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
                [$uncomputedExpr, $computedExpr] = $this->resolveGroupNode($node);
                if ($uncomputedExpr) {
                    $uncomputedRestrictions[] = new Restriction($uncomputedExpr, $node->getCondition(), false);
                }
                if ($computedExpr) {
                    $computedRestrictions[] = new Restriction($computedExpr, $node->getCondition(), true);
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
     * @return mixed An expression
     */
    private function createExprFromRestrictions(array $restrictions)
    {
        return array_reduce(
            $restrictions,
            function ($expr, Restriction $restriction) {
                if (null === $expr) {
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
