<?php

namespace Oro\Bundle\NavigationBundle\Entity\Repository;

use Doctrine\Common\Cache\ArrayCache;
use Doctrine\Common\Cache\CacheProvider;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\Expr\Join;
use Oro\Bundle\NavigationBundle\Entity\MenuUpdateInterface;
use Oro\Bundle\NavigationBundle\Utils\MenuUpdateUtils;
use Oro\Bundle\ScopeBundle\Entity\Scope;

/**
 * Repository for MenuUpdate ORM Entity.
 */
class MenuUpdateRepository extends EntityRepository
{
    /**
     * @var CacheProvider
     */
    private $queryResultCache;

    /**
     * @param CacheProvider $queryResultCache
     */
    public function setQueryResultCache(CacheProvider $queryResultCache)
    {
        $this->queryResultCache = $queryResultCache;
    }

    /**
     * @param string $menuName
     * @param array $scopeIds
     *
     * @return array
     */
    public function findMenuUpdatesByScopeIds($menuName, array $scopeIds)
    {
        $result = [];
        $scopeIds = array_reverse($scopeIds);
        foreach ($scopeIds as $scopeId) {
            $result[] = $this->findMenuUpdatesByScope(
                $menuName,
                $this->getEntityManager()->getReference(Scope::class, $scopeId)
            );
        }

        if ($result) {
            return array_merge(...$result);
        }

        return [];
    }

    /**
     * @param string $menuName
     * @param Scope $scope
     *
     * @return MenuUpdateInterface[]
     */
    public function findMenuUpdatesByScope($menuName, $scope)
    {
        $qb = $this->createQueryBuilder('u');

        return $qb->select(['u', 'titles', 'descriptions'])
            ->leftJoin('u.titles', 'titles')
            ->leftJoin('u.descriptions', 'descriptions')
            ->where($qb->expr()->eq('u.menu', ':menuName'))
            ->andWhere($qb->expr()->eq('u.scope', ':scope'))
            ->orderBy('u.id')
            ->setParameters([
                'menuName' => $menuName,
                'scope' => $scope
            ])
            ->getQuery()
            ->useResultCache(true)
            ->setResultCacheDriver($this->getQueryResultCache())
            ->setResultCacheId(MenuUpdateUtils::generateKey($menuName, $scope))
            ->getResult();
    }

    /**
     * @param MenuUpdateInterface $menuUpdate
     */
    public function updateDependentMenuUpdates(MenuUpdateInterface $menuUpdate)
    {
        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->update($this->getEntityName(), 'u')
            ->set('u.uri', ':uri')
            ->where($qb->expr()->andX(
                $qb->expr()->eq('u.menu', ':menuName'),
                $qb->expr()->eq('u.key', ':menuUpdateKey'),
                $qb->expr()->neq('u.id', ':currentId')
            ))
            ->setParameter('menuName', $menuUpdate->getMenu())
            ->setParameter('menuUpdateKey', $menuUpdate->getKey())
            ->setParameter('currentId', $menuUpdate->getId())
            ->setParameter('uri', $menuUpdate->getUri());

        $qb->getQuery()->execute();
    }

    /**
     * @param MenuUpdateInterface $menuUpdate
     * @return Scope[]
     */
    public function getDependentMenuUpdateScopes(MenuUpdateInterface $menuUpdate)
    {
        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb
            ->select('scope')
            ->distinct(true)
            ->from(Scope::class, 'scope')
            ->innerJoin($this->getEntityName(), 'u', Join::WITH, $qb->expr()->eq('u.scope', 'scope'))
            ->where($qb->expr()->andX(
                $qb->expr()->eq('u.menu', ':menuName'),
                $qb->expr()->eq('u.key', ':menuUpdateKey'),
                $qb->expr()->neq('u.id', ':currentId')
            ))
            ->setParameter('menuName', $menuUpdate->getMenu())
            ->setParameter('menuUpdateKey', $menuUpdate->getKey())
            ->setParameter('currentId', $menuUpdate->getId());

        return $qb->getQuery()->getResult();
    }

    /**
     * @return CacheProvider
     */
    private function getQueryResultCache()
    {
        if (!$this->queryResultCache) {
            $this->queryResultCache = new ArrayCache();
        }

        return $this->queryResultCache;
    }
}
