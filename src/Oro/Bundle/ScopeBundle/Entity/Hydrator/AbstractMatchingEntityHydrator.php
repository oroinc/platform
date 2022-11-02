<?php

namespace Oro\Bundle\ScopeBundle\Entity\Hydrator;

use Doctrine\ORM\Internal\Hydration\AbstractHydrator;

/**
 * Custom hydrator that increases performance when getting the matching entity.
 * Requires matchedScopeId to be selected
 */
abstract class AbstractMatchingEntityHydrator extends AbstractHydrator
{
    public const MATCHED_SCOPE_ID = 'matchedScopeId';

    abstract protected function getRootEntityAlias(): string;

    abstract protected function getEntityClass(): string;

    /**
     * @param mixed $entityId
     * @return bool
     */
    abstract protected function hasScopes($entityId): bool;

    /**
     * @return array
     */
    protected function hydrateAllData()
    {
        $rows = $this->_stmt->fetchAll(\PDO::FETCH_ASSOC);
        foreach ($rows as $key => $row) {
            $id = [key($this->_rsm->aliasMap) => ''];
            $nonemptyComponents = [];
            $rows[$key] = $this->gatherRowData($row, $id, $nonemptyComponents);
        }

        usort($rows, function ($a, $b) {
            if ($a['scalars'][self::MATCHED_SCOPE_ID] === null && $b['scalars'][self::MATCHED_SCOPE_ID] === null) {
                return 0;
            }
            if ($a['scalars'][self::MATCHED_SCOPE_ID] === null) {
                return 1;
            }
            if ($b['scalars'][self::MATCHED_SCOPE_ID] === null) {
                return -1;
            }

            return 0;
        });

        $alias = $this->getRootEntityAlias();
        foreach ($rows as $row) {
            if ($row['scalars'][self::MATCHED_SCOPE_ID] || !$this->hasScopes($row['data'][$alias]['id'])) {
                return [$this->_uow->createEntity($this->getEntityClass(), $row['data'][$alias], $this->_hints)];
            }
        }

        return [];
    }
}
