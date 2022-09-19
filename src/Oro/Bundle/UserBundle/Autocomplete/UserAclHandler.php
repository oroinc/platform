<?php

namespace Oro\Bundle\UserBundle\Autocomplete;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\AttachmentBundle\Provider\PictureSourcesProviderInterface;
use Oro\Bundle\EntityBundle\Provider\EntityNameResolver;
use Oro\Bundle\EntityBundle\Tools\EntityRoutingHelper;
use Oro\Bundle\FormBundle\Autocomplete\SearchHandlerInterface;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\SecurityBundle\Acl\AccessLevel;
use Oro\Bundle\SecurityBundle\Acl\Domain\OneShotIsGrantedObserver;
use Oro\Bundle\SecurityBundle\Acl\Extension\EntityAclExtension;
use Oro\Bundle\SecurityBundle\Acl\Extension\ObjectIdentityHelper;
use Oro\Bundle\SecurityBundle\Acl\Voter\AclVoterInterface;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Oro\Bundle\SecurityBundle\Owner\OwnerTreeProvider;
use Oro\Bundle\UserBundle\Entity\User;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * Autocomplete search handler to get a list of available users that can be set as owner of the ACL protected entity
 * with ownership type "User".
 */
class UserAclHandler implements SearchHandlerInterface
{
    private ManagerRegistry $doctrine;
    private PictureSourcesProviderInterface $pictureSourcesProvider;
    private AuthorizationCheckerInterface $authorizationChecker;
    private TokenAccessorInterface $tokenAccessor;
    private OwnerTreeProvider $treeProvider;
    private EntityRoutingHelper $entityRoutingHelper;
    private EntityNameResolver $entityNameResolver;
    private AclVoterInterface $aclVoter;

    public function __construct(
        ManagerRegistry $doctrine,
        PictureSourcesProviderInterface $pictureSourcesProvider,
        AuthorizationCheckerInterface $authorizationChecker,
        TokenAccessorInterface $tokenAccessor,
        OwnerTreeProvider $treeProvider,
        EntityRoutingHelper $entityRoutingHelper,
        EntityNameResolver $entityNameResolver,
        AclVoterInterface $aclVoter
    ) {
        $this->doctrine = $doctrine;
        $this->pictureSourcesProvider = $pictureSourcesProvider;
        $this->authorizationChecker = $authorizationChecker;
        $this->tokenAccessor = $tokenAccessor;
        $this->treeProvider = $treeProvider;
        $this->entityRoutingHelper = $entityRoutingHelper;
        $this->entityNameResolver = $entityNameResolver;
        $this->aclVoter = $aclVoter;
    }

    /**
     * {@inheritDoc}
     */
    public function search($query, $page, $perPage, $searchById = false)
    {
        [$search, $entityClass, $permission, $entityId, $excludeCurrentUser] = explode(';', $query);
        $entityClass = $this->entityRoutingHelper->resolveEntityClass($entityClass);

        $hasMore = false;
        $object = $entityId
            ? $this->doctrine->getRepository($entityClass)->find((int)$entityId)
            : ObjectIdentityHelper::encodeIdentityString(EntityAclExtension::NAME, $entityClass);
        $observer = new OneShotIsGrantedObserver();
        $this->aclVoter->addOneShotIsGrantedObserver($observer);
        if ($this->authorizationChecker->isGranted($permission, $object)) {
            if ($searchById) {
                $results = $this->doctrine->getRepository(User::class)->findBy(['id' => explode(',', $search)]);
            } else {
                $page = (int)$page > 0 ? (int)$page : 1;
                $perPage = (int)$perPage > 0 ? (int)$perPage : 10;
                $firstResult = ($page - 1) * $perPage;
                $perPage++;

                /** @var User $user */
                $user = $this->tokenAccessor->getUser();
                /** @var Organization $organization */
                $organization = $this->tokenAccessor->getOrganization();
                $queryBuilder = $this->createQueryBuilder($search);
                if ($excludeCurrentUser) {
                    $queryBuilder
                        ->andWhere('user.id != :userId')
                        ->setParameter('userId', $user->getId());
                }
                $queryBuilder
                    ->setFirstResult($firstResult)
                    ->setMaxResults($perPage);
                $results = $this->applyAcl($queryBuilder, $observer->getAccessLevel(), $user, $organization)
                    ->getResult();

                $hasMore = count($results) === $perPage;
                if ($hasMore) {
                    $results = \array_slice($results, 0, $perPage - 1);
                }
            }

            $resultsData = [];
            foreach ($results as $item) {
                $resultsData[] = $this->convertItem($item);
            }
        } else {
            $resultsData = [];
        }

        return [
            'results' => $resultsData,
            'more'    => $hasMore
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function getProperties()
    {
        return ['id', 'username', 'namePrefix', 'firstName', 'middleName', 'lastName', 'nameSuffix', 'email'];
    }

    /**
     * {@inheritDoc}
     */
    public function getEntityName()
    {
        return User::class;
    }

    /**
     * {@inheritDoc}
     */
    public function convertItem($item)
    {
        /** @var User $item */
        $result = [];
        $result['id'] = $item->getId();
        $result['username'] = $item->getUsername();
        $result['namePrefix'] = $item->getNamePrefix();
        $result['firstName'] = $item->getFirstName();
        $result['middleName'] = $item->getMiddleName();
        $result['lastName'] = $item->getLastName();
        $result['nameSuffix'] = $item->getNameSuffix();
        $result['email'] = $item->getEmail();
        $result['avatar'] = $this->pictureSourcesProvider->getFilteredPictureSources(
            $item->getAvatar(),
            UserSearchHandler::IMAGINE_AVATAR_FILTER
        );
        $result['fullName'] = $this->entityNameResolver->getName($item);

        return $result;
    }

    protected function applyAcl(
        QueryBuilder $queryBuilder,
        int $accessLevel,
        User $user,
        Organization $organization
    ): Query {
        if (AccessLevel::BASIC_LEVEL === $accessLevel) {
            $queryBuilder
                ->andWhere('user.id = :aclUserId')
                ->setParameter('aclUserId', $user->getId());

            return $queryBuilder->getQuery();
        }

        if ($accessLevel < AccessLevel::GLOBAL_LEVEL) {
            if ($accessLevel === AccessLevel::LOCAL_LEVEL) {
                $resultBuIds = $this->treeProvider->getTree()->getUserBusinessUnitIds(
                    $user->getId(),
                    $organization->getId()
                );
            } else {
                // AccessLevel::DEEP_LEVEL
                $resultBuIds = $this->treeProvider->getTree()->getUserSubordinateBusinessUnitIds(
                    $user->getId(),
                    $organization->getId()
                );
            }
            $queryBuilder->join('user.businessUnits', 'bu');
            if ($resultBuIds) {
                $queryBuilder
                    ->andWhere('bu.id IN (:resultBuIds)')
                    ->setParameter('resultBuIds', $resultBuIds);
            } else {
                $queryBuilder->andWhere('1 = 0');
            }
        }

        // data should be limited by organization
        $queryBuilder
            ->join('user.organizations', 'org')
            ->andWhere('org.id = :aclOrganizationId')
            ->setParameter('aclOrganizationId', $organization->getId());

        return $queryBuilder->getQuery();
    }

    private function createQueryBuilder(string $search): QueryBuilder
    {
        /** @var EntityManagerInterface $em */
        $em = $this->doctrine->getManagerForClass(User::class);
        $queryBuilder = $em->createQueryBuilder()
            ->select('user')
            ->from(User::class, 'user');
        if ($search) {
            $queryBuilder->where($queryBuilder->expr()->orX(
                'LOWER(CONCAT(user.firstName, CONCAT(\' \', user.lastName))) LIKE :search',
                'LOWER(CONCAT(user.lastName, CONCAT(\' \', user.firstName))) LIKE :search',
                'LOWER(user.username) LIKE :search',
                'LOWER(user.email) LIKE :search'
            ));
            $queryBuilder->setParameter('search', '%' . str_replace(' ', '%', strtolower($search)) . '%');
        }
        $queryBuilder
            ->andWhere('user.enabled = :enabled')
            ->setParameter('enabled', true);

        return $queryBuilder;
    }
}
