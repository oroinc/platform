<?php

namespace Oro\Bundle\UserBundle\Autocomplete;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\AttachmentBundle\Manager\AttachmentManager;
use Oro\Bundle\EntityBundle\Provider\EntityNameResolver;
use Oro\Bundle\EntityBundle\Tools\EntityRoutingHelper;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Oro\Bundle\FormBundle\Autocomplete\SearchHandlerInterface;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\SecurityBundle\Acl\AccessLevel;
use Oro\Bundle\SecurityBundle\Acl\Domain\OneShotIsGrantedObserver;
use Oro\Bundle\SecurityBundle\Acl\Voter\AclVoter;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Oro\Bundle\SecurityBundle\ORM\Walker\OwnershipConditionDataBuilder;
use Oro\Bundle\SecurityBundle\Owner\OwnerTreeProvider;
use Oro\Bundle\UserBundle\Entity\User;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Autocomplete search handler for users with ACL access level protection
 */
class UserAclHandler implements SearchHandlerInterface
{
    /** @var EntityManager */
    protected $em;

    /** @var AttachmentManager */
    protected $attachmentManager;

    /** @var string */
    protected $className;

    /** @var array */
    protected $fields = [];

    /** @var EntityNameResolver */
    protected $entityNameResolver;

    /** @var EntityRoutingHelper */
    protected $entityRoutingHelper;

    /** @var AclVoter|null */
    protected $aclVoter;

    /** @var OwnershipConditionDataBuilder */
    protected $builder;

    /** @var AuthorizationCheckerInterface */
    protected $authorizationChecker;

    /** @var TokenAccessorInterface */
    protected $tokenAccessor;

    /** @var OwnerTreeProvider */
    protected $treeProvider;

    /**
     * @param EntityManager                 $em
     * @param AttachmentManager             $attachmentManager
     * @param string                        $className
     * @param AuthorizationCheckerInterface $authorizationChecker
     * @param TokenAccessorInterface        $tokenAccessor
     * @param OwnerTreeProvider             $treeProvider
     * @param EntityRoutingHelper $entityRoutingHelper
     * @param AclVoter|null                 $aclVoter
     */
    public function __construct(
        EntityManager $em,
        AttachmentManager $attachmentManager,
        $className,
        AuthorizationCheckerInterface $authorizationChecker,
        TokenAccessorInterface $tokenAccessor,
        OwnerTreeProvider $treeProvider,
        EntityRoutingHelper $entityRoutingHelper,
        AclVoter $aclVoter = null
    ) {
        $this->em = $em;
        $this->attachmentManager = $attachmentManager;
        $this->className = $className;
        $this->authorizationChecker = $authorizationChecker;
        $this->tokenAccessor = $tokenAccessor;
        $this->treeProvider = $treeProvider;
        $this->entityRoutingHelper = $entityRoutingHelper;
        $this->aclVoter = $aclVoter;
    }

    /**
     * {@inheritdoc}
     *
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function search($query, $page, $perPage, $searchById = false)
    {
        list($search, $entityClass, $permission, $entityId, $excludeCurrentUser) = explode(';', $query);
        $entityClass = $this->entityRoutingHelper->resolveEntityClass($entityClass);

        $hasMore  = false;
        $object   = $entityId
            ? $this->em->getRepository($entityClass)->find((int)$entityId)
            : 'entity:' . $entityClass;
        $observer = new OneShotIsGrantedObserver();
        $this->aclVoter->addOneShotIsGrantedObserver($observer);
        if ($this->authorizationChecker->isGranted($permission, $object)) {
            if ($searchById) {
                $results = $this->searchById($search);
            } else {
                $page        = (int)$page > 0 ? (int)$page : 1;
                $perPage     = (int)$perPage > 0 ? (int)$perPage : 10;
                $firstResult = ($page - 1) * $perPage;
                $perPage += 1;

                $user = $this->tokenAccessor->getUser();
                $organization = $this->tokenAccessor->getOrganization();
                $queryBuilder = $this->createQueryBuilder();
                $this->addSearchCriteria($queryBuilder, $search);
                if ((boolean)$excludeCurrentUser) {
                    $this->excludeUser($queryBuilder, $user);
                }
                $this->addAdditionalFilterCriteria($queryBuilder);
                $queryBuilder
                    ->setFirstResult($firstResult)
                    ->setMaxResults($perPage);
                $query = $this->applyAcl($queryBuilder, $observer->getAccessLevel(), $user, $organization);
                $results = $query->getResult();

                $hasMore = count($results) == $perPage;
                if ($hasMore) {
                    $results = array_slice($results, 0, $perPage - 1);
                }
            }

            $resultsData = [];
            foreach ($results as $user) {
                $resultsData[] = $this->convertItem($user);
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
     * @param string $query
     *
     * @return User
     */
    protected function searchById($query)
    {
        return $this->em->getRepository('OroUserBundle:User')->findBy(['id' => explode(',', $query)]);
    }

    /**
     * {@inheritdoc}
     */
    public function getProperties()
    {
        return $this->fields;
    }

    /**
     * {@inheritdoc}
     */
    public function getEntityName()
    {
        return $this->className;
    }

    /**
     * @param string[] $properties
     */
    public function setProperties(array $properties)
    {
        $this->fields = $properties;
    }

    /**
     * @param EntityNameResolver $entityNameResolver
     */
    public function setEntityNameResolver(EntityNameResolver $entityNameResolver)
    {
        $this->entityNameResolver = $entityNameResolver;
    }

    /**
     * {@inheritdoc}
     */
    public function convertItem($user)
    {
        $result = [];
        foreach ($this->fields as $field) {
            $result[$field] = $this->getPropertyValue($field, $user);
        }

        $result['avatar'] = $this->getUserAvatar($user);

        if (!$this->entityNameResolver) {
            throw new \RuntimeException('Name resolver must be configured');
        }
        $result['fullName'] = $this->entityNameResolver->getName($user);

        return $result;
    }

    /**
     * @param $user
     *
     * @return string|null
     */
    protected function getUserAvatar($user)
    {
        $avatar = $this->getPropertyValue('avatar', $user);
        if (!$avatar) {
            return null;
        }

        return $this->attachmentManager->getFilteredImageUrl(
            $avatar,
            UserSearchHandler::IMAGINE_AVATAR_FILTER
        );
    }

    /**
     * @param string       $name
     * @param object|array $item
     *
     * @return mixed
     */
    protected function getPropertyValue($name, $item)
    {
        $result = null;

        if (is_object($item)) {
            $method = 'get' . str_replace(' ', '', str_replace('_', ' ', ucwords($name)));
            if (method_exists($item, $method)) {
                $result = $item->$method();
            } elseif (isset($item->$name)) {
                $result = $item->$name;
            }
        } elseif (is_array($item) && array_key_exists($name, $item)) {
            $result = $item[$name];
        }

        return $result;
    }

    /**
     * Gets a query builder can be used to retrieve users
     *
     * @return QueryBuilder
     */
    protected function createQueryBuilder()
    {
        return $this->em->createQueryBuilder()
            ->select('user')
            ->from('Oro\Bundle\UserBundle\Entity\User', 'user');
    }

    /**
     * Adds a search criteria to the given query builder based on the given query string
     *
     * @param QueryBuilder $queryBuilder The query builder
     * @param string       $search       The search string
     */
    protected function addSearchCriteria(QueryBuilder $queryBuilder, $search)
    {
        $queryBuilder
            ->add(
                'where',
                $queryBuilder->expr()->orX(
                    $queryBuilder->expr()->like(
                        $queryBuilder->expr()->lower(
                            $queryBuilder->expr()->concat(
                                'user.firstName',
                                $queryBuilder->expr()->concat(
                                    $queryBuilder->expr()->literal(' '),
                                    'user.lastName'
                                )
                            )
                        ),
                        '?1'
                    ),
                    $queryBuilder->expr()->like(
                        $queryBuilder->expr()->lower(
                            $queryBuilder->expr()->concat(
                                'user.lastName',
                                $queryBuilder->expr()->concat(
                                    $queryBuilder->expr()->literal(' '),
                                    'user.firstName'
                                )
                            )
                        ),
                        '?1'
                    ),
                    $queryBuilder->expr()->like($queryBuilder->expr()->lower('user.username'), '?1'),
                    $queryBuilder->expr()->like($queryBuilder->expr()->lower('user.email'), '?1')
                )
            )
            ->setParameter(1, '%' . str_replace(' ', '%', strtolower($search)) . '%');
    }

    /**
     * Add additional filter
     *
     * @param QueryBuilder $queryBuilder
     */
    protected function addAdditionalFilterCriteria(QueryBuilder $queryBuilder)
    {
        $queryBuilder
            ->andWhere('user.enabled = :enabled')
            ->setParameter('enabled', true);
    }

    /**
     * Returns ACL protected query built based on the given query builder
     *
     * @param QueryBuilder $queryBuilder
     * @param string       $accessLevel
     * @param User         $user
     * @param Organization $organization
     *
     * @return Query
     */
    protected function applyAcl(QueryBuilder $queryBuilder, $accessLevel, User $user, Organization $organization)
    {
        if ($accessLevel == AccessLevel::BASIC_LEVEL) {
            $queryBuilder->andWhere($queryBuilder->expr()->eq('user.id', ':aclUserId'))
                ->setParameter('aclUserId', $user->getId());
        } elseif ($accessLevel == AccessLevel::GLOBAL_LEVEL) {
            $queryBuilder->join('user.organizations', 'org')
                ->andWhere($queryBuilder->expr()->eq('org.id', ':aclOrganizationId'))
                ->setParameter('aclOrganizationId', $organization->getId());
        } elseif ($accessLevel !== AccessLevel::SYSTEM_LEVEL) {
            if ($accessLevel == AccessLevel::LOCAL_LEVEL) {
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
                $queryBuilder->andWhere($queryBuilder->expr()->in('bu.id', ':resultBuIds'))
                    ->setParameter('resultBuIds', $resultBuIds);
            } else {
                $queryBuilder->andWhere('1 = 0');
            }
        }

        return $queryBuilder->getQuery();
    }

    /**
     * Adds a condition excluding user from the list
     *
     * @param QueryBuilder  $queryBuilder
     * @param UserInterface $user
     */
    protected function excludeUser(QueryBuilder $queryBuilder, UserInterface $user)
    {
        $queryBuilder->andWhere('user.id != :userId');
        $queryBuilder->setParameter('userId', $user->getId());
    }
}
