<?php

namespace Oro\Bundle\UserBundle\Autocomplete;

use Doctrine\ORM\Query;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Symfony\Component\Security\Core\SecurityContextInterface;
use Symfony\Component\Security\Core\User\UserInterface;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\QueryBuilder;

use Oro\Bundle\AttachmentBundle\Manager\AttachmentManager;
use Oro\Bundle\EntityConfigBundle\DependencyInjection\Utils\ServiceLink;
use Oro\Bundle\FormBundle\Autocomplete\SearchHandlerInterface;
use Oro\Bundle\LocaleBundle\Formatter\NameFormatter;

use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\SecurityBundle\Acl\AccessLevel;
use Oro\Bundle\SecurityBundle\Acl\Domain\OneShotIsGrantedObserver;
use Oro\Bundle\SecurityBundle\Acl\Voter\AclVoter;
use Oro\Bundle\SecurityBundle\ORM\Walker\OwnershipConditionDataBuilder;
use Oro\Bundle\SecurityBundle\Owner\OwnerTreeProvider;

/**
 * Autocomplete search handler for users with ACL access level protection
 *
 * Class UserAclHandler
 *
 * @package Oro\Bundle\UserBundle\Autocomplete
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

    /** @var NameFormatter */
    protected $nameFormatter;

    /** @var AclVoter */
    protected $aclVoter;

    /** @var OwnershipConditionDataBuilder */
    protected $builder;

    /** @var ServiceLink */
    protected $securityContextLink;

    /** @var OwnerTreeProvider */
    protected $treeProvider;

    /**
     * @param EntityManager     $em
     * @param AttachmentManager $attachmentManager
     * @param string            $className
     * @param ServiceLink       $securityContextLink
     * @param OwnerTreeProvider $treeProvider
     * @param AclVoter          $aclVoter
     */
    public function __construct(
        EntityManager $em,
        AttachmentManager $attachmentManager,
        $className,
        ServiceLink $securityContextLink,
        OwnerTreeProvider $treeProvider,
        AclVoter $aclVoter = null
    ) {
        $this->em                  = $em;
        $this->attachmentManager   = $attachmentManager;
        $this->className           = $className;
        $this->aclVoter            = $aclVoter;
        $this->securityContextLink = $securityContextLink;
        $this->treeProvider        = $treeProvider;
    }

    /**
     * {@inheritdoc}
     *
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function search($query, $page, $perPage, $searchById = false)
    {
        list ($search, $entityClass, $permission, $entityId, $excludeCurrentUser) = explode(';', $query);
        $entityClass = $this->decodeClassName($entityClass);

        $hasMore  = false;
        $object   = $entityId
            ? $this->em->getRepository($entityClass)->find((int)$entityId)
            : 'entity:' . $entityClass;
        $observer = new OneShotIsGrantedObserver();
        $this->aclVoter->addOneShotIsGrantedObserver($observer);
        if ($this->getSecurityContext()->isGranted($permission, $object)) {
            $results = [];
            if ($searchById) {
                $results = $this->searchById($search);
            } else {
                $page        = (int)$page > 0 ? (int)$page : 1;
                $perPage     = (int)$perPage > 0 ? (int)$perPage : 10;
                $firstResult = ($page - 1) * $perPage;
                $perPage += 1;

                $user         = $this->getSecurityContext()->getToken()->getUser();
                $organization = $this->getSecurityContext()->getToken()->getOrganizationContext();
                $queryBuilder = $this->createQueryBuilder();
                $this->addSearchCriteria($queryBuilder, $search);
                if ((boolean)$excludeCurrentUser) {
                    $this->excludeUser($queryBuilder, $user);
                }
                $queryBuilder
                    ->setFirstResult($firstResult)
                    ->setMaxResults($perPage);
                $query = $this->applyAcl($queryBuilder, $observer->getAccessLevel(), $user, $organization);
                $results = $query->getResult();

                $hasMore = count($results) == $perPage;
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
     * @param NameFormatter $nameFormatter
     */
    public function setNameFormatter(NameFormatter $nameFormatter)
    {
        $this->nameFormatter = $nameFormatter;
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

        if (!$this->nameFormatter) {
            throw new \RuntimeException('Name formatter must be configured');
        }
        $result['fullName'] = $this->nameFormatter->format($user);

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
                        $queryBuilder->expr()->concat(
                            'user.firstName',
                            $queryBuilder->expr()->concat(
                                $queryBuilder->expr()->literal(' '),
                                'user.lastName'
                            )
                        ),
                        '?1'
                    ),
                    $queryBuilder->expr()->like(
                        $queryBuilder->expr()->concat(
                            'user.lastName',
                            $queryBuilder->expr()->concat(
                                $queryBuilder->expr()->literal(' '),
                                'user.firstName'
                            )
                        ),
                        '?1'
                    ),
                    $queryBuilder->expr()->like('user.username', '?1')
                )
            )
            ->setParameter(1, '%' . str_replace(' ', '%', $search) . '%');
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
            $queryBuilder->andWhere($queryBuilder->expr()->in('user.id', [$user->getId()]));
        } elseif ($accessLevel == AccessLevel::GLOBAL_LEVEL) {
            $queryBuilder->join('user.organizations', 'org')
                ->andWhere($queryBuilder->expr()->in('org.id', [$organization->getId()]));
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
            $queryBuilder->join('user.businessUnits', 'bu')
                ->andWhere($queryBuilder->expr()->in('bu.id', $resultBuIds));
        }

        return $queryBuilder->getQuery();
    }

    /**
     * @return SecurityContextInterface
     */
    protected function getSecurityContext()
    {
        return $this->securityContextLink->getService();
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

    /**
     * Decodes the given string into the class name
     *
     * @param string $className The encoded class name
     *
     * @return string The class name
     *
     * @deprecated since 1.6. Will be removed in 2.0. Use oro_entity.routing_helper->decodeClassName($entityName);
     */
    public function decodeClassName($className)
    {
        $result = str_replace('_', '\\', $className);
        if (strpos($result, ExtendHelper::ENTITY_NAMESPACE) === 0) {
            // a custom entity can contain _ in class name
            $result = ExtendHelper::ENTITY_NAMESPACE . substr($className, strlen(ExtendHelper::ENTITY_NAMESPACE));
        }

        return $result;
    }
}
