<?php

namespace Oro\Bundle\SecurityBundle\Owner;

use Doctrine\Common\Cache\CacheProvider;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query;
use Oro\Bundle\EntityBundle\Tools\DatabaseChecker;
use Oro\Bundle\SecurityBundle\Owner\Metadata\OwnershipMetadataProviderInterface;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Component\DoctrineUtils\ORM\QueryUtil;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class OwnerTreeProvider extends AbstractOwnerTreeProvider
{
    /** @var ManagerRegistry */
    private $doctrine;

    /** @var TokenStorageInterface */
    private $tokenStorage;

    /** @var OwnershipMetadataProviderInterface */
    private $ownershipMetadataProvider;

    /**
     * @param ManagerRegistry                    $doctrine
     * @param DatabaseChecker                    $databaseChecker
     * @param CacheProvider                      $cache
     * @param OwnershipMetadataProviderInterface $ownershipMetadataProvider
     * @param TokenStorageInterface              $tokenStorage
     */
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
    public function supports()
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
    protected function fillTree(OwnerTreeBuilderInterface $tree)
    {
        $ownershipMetadataProvider = $this->getOwnershipMetadataProvider();
        $userClass = $ownershipMetadataProvider->getUserClass();
        $businessUnitClass = $ownershipMetadataProvider->getBusinessUnitClass();
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
        foreach ($businessUnits as $businessUnit) {
            $orgId = $this->getId($businessUnit, $columnMap['orgId']);
            if (null !== $orgId) {
                $buId = $this->getId($businessUnit, $columnMap['id']);
                $tree->addBusinessUnit($buId, $orgId);
                $tree->addBusinessUnitRelation($buId, $this->getId($businessUnit, $columnMap['parentId']));
            }
        }

        $tree->buildTree();

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

    /**
     * @param array  $item
     * @param string $property
     *
     * @return int|null
     */
    protected function getId($item, $property)
    {
        $id = $item[$property];

        return null !== $id ? (int)$id : null;
    }

    /**
     * @param Connection $connection
     * @param Query      $query
     *
     * @return array [rows, columnMap]
     */
    protected function executeQuery(Connection $connection, Query $query)
    {
        $parsedQuery = QueryUtil::parseQuery($query);

        return [
            $connection->executeQuery(QueryUtil::getExecutableSql($query, $parsedQuery)),
            array_flip($parsedQuery->getResultSetMapping()->scalarMappings)
        ];
    }

    /**
     * @param string $className
     *
     * @return EntityManager
     */
    protected function getManagerForClass($className)
    {
        return $this->doctrine->getManagerForClass($className);
    }

    /**
     * @param string $entityClass
     *
     * @return EntityRepository
     */
    protected function getRepository($entityClass)
    {
        return $this->getManagerForClass($entityClass)->getRepository($entityClass);
    }

    /**
     * @return OwnershipMetadataProviderInterface
     */
    protected function getOwnershipMetadataProvider()
    {
        return $this->ownershipMetadataProvider;
    }
}
