<?php

namespace Oro\Bundle\NavigationBundle\Entity\Repository;

use Doctrine\Common\Cache\ArrayCache;
use Doctrine\Common\Cache\CacheProvider;
use Doctrine\ORM\EntityRepository;
use Oro\Bundle\NavigationBundle\Entity\MenuUpdateInterface;
use Oro\Bundle\NavigationBundle\Utils\MenuUpdateUtils;
use Oro\Bundle\ScopeBundle\Entity\Scope;

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
     * @param array  $scopeIds
     *
     * @return array
     */
    public function findMenuUpdatesByScopeIds($menuName, array $scopeIds)
    {
        $result = [];
        $scopeIds = array_reverse($scopeIds);
        foreach ($scopeIds as $scopeId) {
            $result = array_merge(
                $result,
                $this->findMenuUpdatesByScope(
                    $menuName,
                    $this->getEntityManager()->getReference(Scope::class, $scopeId)
                )
            );
        }

        return $result;
    }

    /**
     * @param string $menuName
     * @param Scope  $scope
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
