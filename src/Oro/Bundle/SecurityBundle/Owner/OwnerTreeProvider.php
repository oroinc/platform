<?php

namespace Oro\Bundle\SecurityBundle\Owner;

use Doctrine\Common\Cache\CacheProvider;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\EntityBundle\Tools\DatabaseChecker;
use Oro\Bundle\SecurityBundle\Owner\Metadata\OwnershipMetadataProviderInterface;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Component\DoctrineUtils\ORM\QueryUtil;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * The provider for owner tree.
 */
class OwnerTreeProvider extends AbstractOwnerTreeProvider
{
    /** @var ManagerRegistry */
    private $doctrine;

    /** @var TokenStorageInterface */
    private $tokenStorage;

    /** @var OwnershipMetadataProviderInterface */
    private $ownershipMetadataProvider;

    public function __construct(
        ManagerRegistry $doctrine,
        DatabaseChecker $databaseChecker,
        CacheProvider $cache,
        OwnershipMetadataProviderInterface $ownershipMetadataProvider,
        TokenStorageInterface $tokenStorage
    ) {
        parent::__construct($databaseChecker, $cache);
        $this->doctrine = $doctrine;
        $this->ownershipMetadataProvider = $ownershipMetadataProvider;
        $this->tokenStorage = $tokenStorage;
    }

    /**
     * {@inheritdoc}
     */
    public function supports(): bool
    {
        $token = $this->tokenStorage->getToken();
        if (null === $token) {
            return false;
        }

        return $token->getUser() instanceof User;
    }

    /**
     * {@inheritdoc}
     */
    protected function fillTree(OwnerTreeBuilderInterface $tree): void
    {
        $userClass = $this->ownershipMetadataProvider->getUserClass();
        $businessUnitClass = $this->ownershipMetadataProvider->getBusinessUnitClass();
        $connection = $this->getManagerForClass($userClass)->getConnection();

        list($businessUnits, $columnMap) = $this->executeQuery(
            $connection,
            $this
                ->getRepository($businessUnitClass)
                ->createQueryBuilder('bu')
                ->select(
                    'bu.id, IDENTITY(bu.organization) orgId, IDENTITY(bu.owner) parentId'
                    . ', (CASE WHEN bu.owner IS NULL THEN 0 ELSE 1 END) AS HIDDEN ORD'
                )
                ->addOrderBy('ORD, parentId', 'ASC')
                ->getQuery()
        );

        $businessUnitRelations = [];

        foreach ($businessUnits as $businessUnit) {
            $orgId = $this->getId($businessUnit, $columnMap['orgId']);
            if (null !== $orgId) {
                $buId = $this->getId($businessUnit, $columnMap['id']);
                $tree->addBusinessUnit($buId, $orgId);
                $businessUnitRelations[$buId] = $this->getId($businessUnit, $columnMap['parentId']);
            }
        }

        $businessUnitRelations = $this->rearrangeBusinessUnitRelations($businessUnitRelations);
        $this->setSubordinateBusinessUnitIds($tree, $this->buildTree($businessUnitRelations, $businessUnitClass));

        list($users, $columnMap) = $this->executeQuery(
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

    /**
     * @param Connection $connection
     * @param Query      $query
     *
     * @return array [rows, columnMap]
     */
    private function executeQuery(Connection $connection, Query $query): array
    {
        $parsedQuery = QueryUtil::parseQuery($query);

        return [
            $connection->executeQuery(QueryUtil::getExecutableSql($query, $parsedQuery)),
            array_flip($parsedQuery->getResultSetMapping()->scalarMappings)
        ];
    }

    protected function setSubordinateBusinessUnitIds(OwnerTreeBuilderInterface $tree, $businessUnits)
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

    /**
     * Moves parent business unit before child business unit, of needs.
     *
     * @param array $businessUnitRelations
     * @return array
     */
    private function rearrangeBusinessUnitRelations(array $businessUnitRelations): array
    {
        $relations = [];

        foreach ($businessUnitRelations as $buId => $parentId) {
            if ($parentId && !array_key_exists($parentId, $relations)
                && array_key_exists($parentId, $businessUnitRelations)
            ) {
                $relations[$parentId] = $businessUnitRelations[$parentId];
                unset($businessUnitRelations[$parentId]);
            }

            $relations[$buId] = $parentId;
        }

        return $relations;
    }
}
