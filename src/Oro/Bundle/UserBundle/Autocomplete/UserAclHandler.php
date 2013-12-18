<?php

namespace Oro\Bundle\UserBundle\Autocomplete;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\Security\Core\SecurityContextInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Liip\ImagineBundle\Imagine\Cache\CacheManager;

use Oro\Bundle\EntityConfigBundle\DependencyInjection\Utils\ServiceLink;
use Oro\Bundle\FormBundle\Autocomplete\SearchHandlerInterface;
use Oro\Bundle\LocaleBundle\Formatter\NameFormatter;
use Oro\Bundle\SecurityBundle\Acl\AccessLevel;
use Oro\Bundle\SecurityBundle\Acl\Domain\OneShotIsGrantedObserver;
use Oro\Bundle\SecurityBundle\Acl\Voter\AclVoter;
use Oro\Bundle\SecurityBundle\ORM\Walker\OwnershipConditionDataBuilder;
use Oro\Bundle\SecurityBundle\Owner\OwnerTreeProvider;

/**
 * Autocomplite search handler for users with ACL access level protection
 *
 * Class UserAclHandler
 * @package Oro\Bundle\UserBundle\Autocomplete
 */
class UserAclHandler implements SearchHandlerInterface
{

    /**
     * @var ObjectManager
     */
    protected $em;

    /**
     * @var CacheManager
     */
    protected $cache;

    protected $className;

    /**
     * @var array
     */
    protected $fields;

    /**
     * @var NameFormatter
     */
    protected $nameFormatter;

    /**
     * @var AclVoter
     */
    protected $aclVoter;

    /**
     * @var OwnershipConditionDataBuilder
     */
    protected $builder;

    /**
     * @var ServiceLink
     */
    protected $securityContextLink;

    /**
     * @var OwnerTreeProvider
     */
    protected $treeProvider;

    /**
     * @param ObjectManager $em
     * @param CacheManager $cache
     * @param $className
     * @param $fields
     * @param ServiceLink $securityContextLink
     * @param OwnerTreeProvider $treeProvider
     * @param AclVoter $aclVoter
     */
    public function __construct(
        ObjectManager $em,
        CacheManager $cache,
        $className,
        $fields,
        ServiceLink $securityContextLink,
        OwnerTreeProvider $treeProvider,
        AclVoter $aclVoter = null
    ) {
        $this->em = $em;
        $this->cache = $cache;
        $this->className = $className;
        $this->fields = $fields;
        $this->aclVoter = $aclVoter;
        $this->securityContextLink = $securityContextLink;
        $this->treeProvider = $treeProvider;
    }

    /**
     * @inheritdoc
     */
    public function search($query, $page, $perPage)
    {

        list ($search, $entityClass, $permission, $entityId) = explode(';', $query);
        $entityClass = str_replace('_', '\\', $entityClass);

        if ($entityId) {
            $object = $this->em->getRepository($entityClass)->find((int)$entityId);
        } else {
            $object = 'entity:' . $entityClass;
        }

        $observer = new OneShotIsGrantedObserver();
        $this->aclVoter->addOneShotIsGrantedObserver($observer);
        $isGranted = $this->getSecurityContext()->isGranted($permission, $object);

        if ($isGranted) {
            $user = $this->getSecurityContext()->getToken()->getUser();
            $queryBuilder = $this->getSearchQueryBuilder($search);
            $this->addAcl($queryBuilder, $observer->getAccessLevel(), $user);
            $results = $queryBuilder->getQuery()->getResult();

            $resultsData = [];
            foreach ($results as $user) {
                $resultsData[] = $this->convertItem($user);
            }
        } else {
            $resultsData = [];
        }

        return [
            'results' => $resultsData,
            'more' => false
        ];
    }

    /**
     * @inheritdoc
     */
    public function getProperties()
    {
        return $this->fields;
    }

    /**
     * @inheritdoc
     */
    public function getEntityName()
    {
        return $this->className;
    }

    /**
     * @param NameFormatter $nameFormatter
     */
    public function setNameFormatter(NameFormatter $nameFormatter)
    {
        $this->nameFormatter = $nameFormatter;
    }

    /**
     * @inheritdoc
     */
    public function convertItem($user)
    {
        $result = [];
        foreach ($this->fields as $field) {
            $result[$field] = $this->getPropertyValue($field, $user);
        }
        $result['avatar'] = null;

        $imagePath = $this->getPropertyValue('imagePath', $user);
        if ($imagePath) {
            $result['avatar'] = $this->cache->getBrowserPath($imagePath, UserSearchHandler::IMAGINE_AVATAR_FILTER);
        }

        if (!$this->nameFormatter) {
            throw new \RuntimeException('Name formatter must be configured');
        }
        $result['fullName'] = $this->nameFormatter->format($user);

        return $result;
    }

    /**
     * @param string $name
     * @param object|array $item
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
     * Get search users query builder
     *
     * @param $search
     * @return QueryBuilder
     */
    protected function getSearchQueryBuilder($search)
    {
        return $this->em->createQueryBuilder()
            ->select(['users'])
            ->from('Oro\Bundle\UserBundle\Entity\User', 'users')
            ->where('users.firstName like :searchString')
            ->orWhere('users.lastName like :searchString')
            ->orWhere('users.username like :searchString')
            ->setParameter('searchString', $search . '%');
    }

    /**
     * Add ACL Check condition to the Query Builder
     *
     * @param QueryBuilder $queryBuilder
     * @param $accessLevel
     * @param UserInterface $user
     */
    protected function addAcl(QueryBuilder $queryBuilder, $accessLevel, UserInterface $user)
    {
        if ($accessLevel == AccessLevel::BASIC_LEVEL) {
            $queryBuilder->andWhere($queryBuilder->expr()->in('users.id', [$user->getId()]));
        } elseif ($accessLevel !== AccessLevel::SYSTEM_LEVEL) {
            if ($accessLevel == AccessLevel::LOCAL_LEVEL) {
                $resultBuIds = $this->treeProvider->getTree()->getUserBusinessUnitIds($user->getId());
            } elseif ($accessLevel == AccessLevel::DEEP_LEVEL) {
                $resultBuIds = $this->treeProvider->getTree()->getUserSubordinateBusinessUnitIds($user->getId());
            } elseif ($accessLevel == AccessLevel::GLOBAL_LEVEL) {
                $resultBuIds = $this->treeProvider->getTree()->getBusinessUnitsIdByUserOrganizations($user->getId());
            }
            $queryBuilder->join('users.owner', 'bu')
                ->andWhere($queryBuilder->expr()->in('bu.id', $resultBuIds));
        }
    }

    /**
     * @return SecurityContextInterface
     */
    protected function getSecurityContext()
    {
        return $this->securityContextLink->getService();
    }
}
