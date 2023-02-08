<?php

namespace Oro\Bundle\NavigationBundle\Entity\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\Query\Expr\Join;
use Oro\Bundle\NavigationBundle\Entity\MenuUpdateInterface;
use Oro\Bundle\NavigationBundle\Utils\MenuUpdateUtils;
use Oro\Bundle\ScopeBundle\Entity\Scope;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\Cache\Adapter\ArrayAdapter;

/**
 * Repository for MenuUpdate ORM Entity.
 */
class MenuUpdateRepository extends ServiceEntityRepository
{
    private ?CacheItemPoolInterface $queryResultCache = null;

    public function setQueryResultCache(CacheItemPoolInterface $queryResultCache): void
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

    public function findMenuUpdatesByScopeIds(string $menuName, array $scopeIds): array
    {
        $menuUpdates = [];
        $scopeIds = array_reverse($scopeIds);

        $qb = $this->createQueryBuilder('u');
        $query = $qb->select('u')
            ->where($qb->expr()->eq('u.menu', ':menuName'))
            ->andWhere($qb->expr()->eq('u.scope', ':scope'))
            ->orderBy('u.id')
            ->setParameter('menuName', $menuName, Types::STRING)
            ->getQuery();

        foreach ($scopeIds as $scopeId) {
            $menuUpdates[] = $query
                ->setParameter('scope', $scopeId, Types::INTEGER)
                ->getResult();
        }

        if ($menuUpdates) {
            $menuUpdates = array_merge(...$menuUpdates);
            $this->loadMenuUpdateDependencies($menuUpdates);

            return $menuUpdates;
        }

        return [];
    }

    public function findMenuUpdatesByScope(string $menuName, Scope $scope): array
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
            ->setResultCache($this->getQueryResultCache())
            ->setResultCacheId(MenuUpdateUtils::generateKey($menuName, $scope))
            ->getResult();
    }

    public function updateDependentMenuUpdates(MenuUpdateInterface $menuUpdate): void
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

    public function getDependentMenuUpdateScopes(MenuUpdateInterface $menuUpdate): array
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

    private function getQueryResultCache(): CacheItemPoolInterface
    {
        if (!$this->queryResultCache) {
            $this->queryResultCache = new ArrayAdapter(0, false);
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

    /**
     * @param string $menuName
     * @param int $scopeId
     * @param string[] $keys
     *
     * @return array<int,MenuUpdateInterface>
     */
    public function findMany(string $menuName, int $scopeId, array $keys): array
    {
        if (!$keys) {
            return [];
        }

        $queryBuilder = $this->createQueryBuilder('mu', 'mu.key');

        return $queryBuilder
            ->where($queryBuilder->expr()->eq('mu.menu', ':menu'))
            ->setParameter('menu', $menuName, Types::STRING)
            ->andWhere($queryBuilder->expr()->in('mu.key', ':keys'))
            ->setParameter('keys', $keys, Connection::PARAM_STR_ARRAY)
            ->andWhere($queryBuilder->expr()->eq('mu.scope', ':scope'))
            ->setParameter('scope', $scopeId, Types::INTEGER)
            ->getQuery()
            ->getResult();
    }
}
