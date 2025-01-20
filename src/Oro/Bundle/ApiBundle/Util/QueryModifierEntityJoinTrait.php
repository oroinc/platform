<?php

namespace Oro\Bundle\ApiBundle\Util;

use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;
use Oro\Component\DoctrineUtils\ORM\QueryBuilderUtil;

/**
 * Provides functionality to join associated entities via INNER JOIN.
 */
trait QueryModifierEntityJoinTrait
{
    private function ensureEntityJoined(
        QueryBuilder $qb,
        string $joinAlias,
        string $join,
        ?string $joinCondition = null
    ): string {
        $entityJoin = $this->getEntityJoin($qb, $join, $joinCondition);
        if (null !== $entityJoin) {
            return $entityJoin->getAlias();
        }

        if (null !== QueryBuilderUtil::findJoinByAlias($qb, $joinAlias)) {
            $i = 0;
            do {
                $i++;
                $newJoinAlias = $joinAlias . $i;
            } while (null !== QueryBuilderUtil::findJoinByAlias($qb, $newJoinAlias));
            $joinAlias = $newJoinAlias;
        }
        if ($joinCondition) {
            $qb->innerJoin($join, $joinAlias, Join::WITH, $this->updateJoinCondition($joinCondition, $joinAlias));
        } else {
            $qb->innerJoin($join, $joinAlias);
        }

        return $joinAlias;
    }

    private function getEntityJoin(QueryBuilder $qb, string $join, ?string $joinCondition = null): ?Join
    {
        $result = null;
        $rootAlias = QueryBuilderUtil::getSingleRootAlias($qb);
        /** @var Join[] $joinObjects */
        foreach ($qb->getDQLPart('join') as $joinGroupAlias => $joinObjects) {
            if ($joinGroupAlias !== $rootAlias) {
                continue;
            }
            foreach ($joinObjects as $key => $joinObject) {
                if ($this->isJoinEqual($joinObject, $join, $joinCondition)) {
                    if ($joinObject->getJoinType() === Join::LEFT_JOIN) {
                        $joinObject = new Join(
                            Join::INNER_JOIN,
                            $joinObject->getJoin(),
                            $joinObject->getAlias(),
                            $joinObject->getConditionType(),
                            $joinObject->getCondition(),
                            $joinObject->getIndexBy()
                        );
                        $joinObjects[$key] = $joinObject;
                        $qb->add('join', [$joinGroupAlias => $joinObjects]);
                    }
                    $result = $joinObject;
                    break;
                }
            }
        }

        return $result;
    }

    private function isJoinEqual(Join $joinObject, string $join, ?string $joinCondition): string
    {
        return
            $joinObject->getJoin() === $join
            && (
                !$joinCondition
                || $joinObject->getCondition() === $this->updateJoinCondition($joinCondition, $joinObject->getAlias())
            );
    }

    private function updateJoinCondition(string $joinCondition, string $joinAlias): string
    {
        return str_replace('{joinAlias}', $joinAlias, $joinCondition);
    }
}
