<?php

namespace Oro\Bundle\NavigationBundle\Entity\Repository;

use Doctrine\Common\Cache\ArrayCache;
use Doctrine\Common\Cache\CacheProvider;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\AbstractQuery;
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

    public function setQueryResultCache(CacheProvider $queryResultCache)
    {
        $this->queryResultCache = $queryResultCache;
    }

    public function getUsedScopesByMenu(): array
    {
        $qb = $this->createQueryBuilder('u');
        $qb->select('DISTINCT IDENTITY(u.scope) as scopeId', 'u.menu');

        $result = [];
        foreach ($qb->getQuery()->getResult(AbstractQuery::HYDRATE_ARRAY) as $row) {
            $result[$row['menu']][] = $row['scopeId'];
        }

        return $result;
    }

    /**
     * @param string $menuName
     * @param array $scopeIds
     *
     * @return array
     */
    public function findMenuUpdatesByScopeIds($menuName, array $scopeIds)
    {
        $menuUpdates = [];
        $scopeIds = array_reverse($scopeIds);

        $qb = $this->createQueryBuilder('u');
        $qb->select('u')
            ->where($qb->expr()->eq('u.menu', ':menuName'))
            ->andWhere($qb->expr()->eq('u.scope', ':scope'))
            ->orderBy('u.id')
            ->setParameter('menuName', $menuName, Types::STRING);

        foreach ($scopeIds as $scopeId) {
            $menuUpdates[] = $qb
                ->setParameter('scope', $scopeId, Types::INTEGER)
                ->getQuery()
                ->getResult();
        }

        if ($menuUpdates) {
            $menuUpdates = array_merge(...$menuUpdates);
            $this->loadMenuUpdateDependencies($menuUpdates);

            return $menuUpdates;
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

    /**
     * Load and hydrate required MenuUpdate dependencies.
     * Perform multi-step hydration optimization to decrease hydration complexity
     *
     * @see https://ocramius.github.io/blog/doctrine-orm-optimization-hydration/
     */
    protected function loadMenuUpdateDependencies(array $menuUpdates): void
    {
        $this->createQueryBuilder('u')
            ->select(['PARTIAL u.{id}', 'titles'])
            ->leftJoin('u.titles', 'titles')
            ->where('u.id IN (:menuUpdates)')
            ->setParameter('menuUpdates', $menuUpdates)
            ->getQuery()
            ->getResult();

        $this->createQueryBuilder('u')
            ->select(['PARTIAL u.{id}', 'descriptions'])
            ->leftJoin('u.descriptions', 'descriptions')
            ->where('u.id IN (:menuUpdates)')
            ->setParameter('menuUpdates', $menuUpdates)
            ->getQuery()
            ->getResult();
    }
}
