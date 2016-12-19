<?php

namespace Oro\Bundle\NavigationBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;

use Oro\Bundle\NavigationBundle\Entity\MenuUpdateInterface;
use Oro\Bundle\ScopeBundle\Entity\Scope;

class MenuUpdateRepository extends EntityRepository
{
    /**
     * @param string $menuName
     * @param array  $scopeIds
     *
     * @return array
     */
    public function findMenuUpdatesByScopeIds($menuName, array $scopeIds)
    {
        $qb = $this->createQueryBuilder('u');
        $qb->innerJoin('u.scope', 's');
        $qb->where($qb->expr()->eq('u.menu', ':menuName'));
        $qb->andWhere($qb->expr()->in('s.id', ':scopeIds'));
        $qb->orderBy('u.id');
        $qb->setParameters([
            'menuName' => $menuName,
            'scopeIds' => $scopeIds
        ]);

        $result = $qb->getQuery()->getResult();

        return $this->applyScopeOrder($result, $scopeIds);
    }

    /**
     * @param string $menuName
     * @param Scope  $scope
     *
     * @return MenuUpdateInterface[]
     */
    public function findMenuUpdatesByScope($menuName, $scope)
    {
        return $this->findBy([
            'menu' => $menuName,
            'scope' => $scope,
        ]);
    }

    /**
     * @param MenuUpdateInterface[] $updates
     * @param array                 $scopeIds
     *
     * @return MenuUpdateInterface[]
     */
    private function applyScopeOrder(array $updates, array $scopeIds)
    {
        $scopeIds = array_reverse($scopeIds);

        $groupedResult = array_fill_keys($scopeIds, []);
        foreach ($updates as $update) {
            $groupedResult[$update->getScope()->getId()][] = $update;
        }

        $result = [];
        foreach ($groupedResult as $group) {
            /** @var MenuUpdateInterface $update */
            foreach ($group as $update) {
                $result[$update->getKey()] = $update;
            }
        }

        return array_values($result);
    }
}
