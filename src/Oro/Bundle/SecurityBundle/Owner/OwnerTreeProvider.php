<?php

namespace Oro\Bundle\SecurityBundle\Owner;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\NativeQuery;
use Doctrine\ORM\Query;
use Doctrine\ORM\Query\ResultSetMapping;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\EntityBundle\Tools\DatabaseChecker;
use Oro\Bundle\SecurityBundle\Owner\Metadata\OwnershipMetadataProviderInterface;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Component\DoctrineUtils\ORM\QueryUtil;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Contracts\Cache\CacheInterface;

/**
 * The provider for owner tree.
 */
class OwnerTreeProvider extends AbstractOwnerTreeProvider
{
    private ManagerRegistry $doctrine;
    private TokenStorageInterface $tokenStorage;
    private OwnershipMetadataProviderInterface $ownershipMetadataProvider;

    public function __construct(
        ManagerRegistry $doctrine,
        DatabaseChecker $databaseChecker,
        CacheInterface $cache,
        OwnershipMetadataProviderInterface $ownershipMetadataProvider,
        TokenStorageInterface $tokenStorage
    ) {
        parent::__construct($databaseChecker, $cache);
        $this->doctrine = $doctrine;
        $this->ownershipMetadataProvider = $ownershipMetadataProvider;
        $this->tokenStorage = $tokenStorage;
    }

    #[\Override]
    public function supports(): bool
    {
        $token = $this->tokenStorage->getToken();
        if (null === $token) {
            return false;
        }

        return $token->getUser() instanceof User;
    }

    #[\Override]
    protected function fillTree(OwnerTreeBuilderInterface $tree): void
    {
        $userClass = $this->ownershipMetadataProvider->getUserClass();
        $businessUnitClass = $this->ownershipMetadataProvider->getBusinessUnitClass();
        $connection = $this->getManagerForClass($userClass)->getConnection();

        $businessUnits = $this->getRecursiveBuildTreeNativeQuery($businessUnitClass)->getResult();

        $businessUnitRelations = [];

        foreach ($businessUnits as $businessUnit) {
            $orgId = $this->getId($businessUnit, 'orgId');
            if (null !== $orgId) {
                $buId = $this->getId($businessUnit, 'id');
                $tree->addBusinessUnit($buId, $orgId);
                $businessUnitRelations[$buId] = $this->getId($businessUnit, 'parentId');
            }
        }

        $this->setSubordinateBusinessUnitIds($tree, $this->buildTree($businessUnitRelations, $businessUnitClass));

        [$users, $columnMap] = $this->executeQuery(
            $connection,
            $this
                ->getRepository($userClass)
                ->createQueryBuilder('u')
                ->innerJoin('u.organizations', 'organizations')
                ->leftJoin('u.businessUnits', 'bu')
                ->select(
                    'u.id as userId, organizations.id as orgId, IDENTITY(u.owner) as owningBuId, bu.id as buId'
                )
                ->addOrderBy('orgId')
                ->getQuery()
        );
        $lastUserId = false;
        $lastOrgId = false;
        $processedUsers = [];
        foreach ($users as $user) {
            $userId = $this->getId($user, $columnMap['userId']);
            $orgId = $this->getId($user, $columnMap['orgId']);
            $owningBuId = $this->getId($user, $columnMap['owningBuId']);
            $buId = $this->getId($user, $columnMap['buId']);
            if ($userId !== $lastUserId && !isset($processedUsers[$userId])) {
                $tree->addUser($userId, $owningBuId);
                $processedUsers[$userId] = true;
            }
            if ($orgId !== $lastOrgId || $userId !== $lastUserId) {
                $tree->addUserOrganization($userId, $orgId);
            }
            $tree->addUserBusinessUnit($userId, $orgId, $buId);
            $lastUserId = $userId;
            $lastOrgId = $orgId;
        }
    }

    private function getId(array $item, string $property): ?int
    {
        $id = $item[$property];
        if (null !== $id) {
            $id = (int)$id;
        }

        return $id;
    }

    private function executeQuery(Connection $connection, Query $query): array
    {
        $parsedQuery = QueryUtil::parseQuery($query);

        return [
            $connection->executeQuery(QueryUtil::getExecutableSql($query, $parsedQuery)),
            array_flip($parsedQuery->getResultSetMapping()->scalarMappings)
        ];
    }

    protected function setSubordinateBusinessUnitIds(OwnerTreeBuilderInterface $tree, $businessUnits): void
    {
        foreach ($businessUnits as $parentId => $businessUnitIds) {
            $tree->setSubordinateBusinessUnitIds($parentId, $businessUnitIds);
        }
    }

    private function getManagerForClass(string $className): EntityManagerInterface
    {
        return $this->doctrine->getManagerForClass($className);
    }

    private function getRepository(string $entityClass): EntityRepository
    {
        return $this->getManagerForClass($entityClass)->getRepository($entityClass);
    }

    private function getRecursiveBuildTreeNativeQuery(string $businessUnitClass): NativeQuery
    {
        $rsm = new ResultSetMapping();
        $rsm->addScalarResult('id', 'id', Types::INTEGER);
        $rsm->addScalarResult('organization_id', 'orgId', Types::INTEGER);
        $rsm->addScalarResult('business_unit_owner_id', 'parentId', Types::INTEGER);

        return $this->getManagerForClass($businessUnitClass)->createNativeQuery('
            WITH RECURSIVE q AS
            (
               SELECT id, business_unit_owner_id, organization_id, 0 as level, ARRAY[id] as path
               FROM oro_business_unit c
               WHERE c.business_unit_owner_id is null
               UNION ALL
               SELECT sub.id, sub.business_unit_owner_id, sub.organization_id, level + 1, path || sub.id
               FROM q
                 JOIN oro_business_unit sub
                   ON sub.business_unit_owner_id = q.id
            )
            SELECT id, business_unit_owner_id, organization_id
            FROM q
            ORDER BY path
        ', $rsm);
    }
}
