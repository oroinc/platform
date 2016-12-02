<?php

namespace Oro\Bundle\NavigationBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;

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
        $qb->setParameters([
            'menuName' => $menuName,
            'scopeIds' => $scopeIds
        ]);

        return $qb->getQuery()->getResult();
    }
}
