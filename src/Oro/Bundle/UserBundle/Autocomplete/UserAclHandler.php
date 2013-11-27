<?php

namespace Oro\Bundle\UserBundle\Autocomplete;

use Oro\Bundle\EntityConfigBundle\DependencyInjection\Utils\ServiceLink;
use Oro\Bundle\FormBundle\Autocomplete\SearchHandlerInterface;
use Oro\Bundle\LocaleBundle\Formatter\NameFormatter;
use Oro\Bundle\SecurityBundle\Acl\Domain\OneShotIsGrantedObserver;
use Oro\Bundle\SecurityBundle\Acl\Voter\AclVoter;
use Oro\Bundle\SecurityBundle\ORM\Walker\OwnershipConditionDataBuilder;

class UserAclHandler implements SearchHandlerInterface
{

    protected $em;

    protected $cache;

    protected $className;

    protected $fields;

    protected $nameFormatter;

    /**
     * @var AclVoter
     */
    protected $aclVoter;

    /**
     * @var OwnershipConditionDataBuilder
     */
    protected $builder;

    protected $securityContextLink;

    public function __construct($em, $cache, $className, $fields, ServiceLink $securityContextLink, AclVoter $aclVoter = null)
    {
        $this->em = $em;
        $this->cache = $cache;
        $this->className = $className;
        $this->fields = $fields;
        $this->aclVoter = $aclVoter;
        $this->securityContextLink = $securityContextLink;
    }

    public function search($query, $page, $perPage)
    {

        list ($search, $entityClass, $permission) = explode(';', $query);
        $entityClass = str_replace('_', '\\', $entityClass);

        $observer = new OneShotIsGrantedObserver();
        $this->aclVoter->addOneShotIsGrantedObserver($observer);
        $isGranted = $this->getSecurityContext()->isGranted($permission, 'entity:' . $entityClass);

        if ($isGranted) {
            $accessLevel = $observer->getAccessLevel();
        }

        $queryBuilder = $this->em->createQueryBuilder()
            ->select(['users'])
            ->from('Oro\Bundle\UserBundle\Entity\User', 'users')
            ->where('users.firstName like :searchString')
            ->orWhere('users.lastName like :searchString')
            ->orWhere('users.username like :searchString')
            ->setParameter('searchString', $search . '%')

        ;
        $results = $queryBuilder->getQuery()->getResult();

        $resultsData = [];
        foreach ($results as $user) {
            $resultsData[] = $this->convertItem($user);
        }

        return [
            'results' => $resultsData,
            'more' => false
        ];
    }

    public function getProperties()
    {
        return $this->fields;
    }

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

    public function convertItem($user)
    {
        $result = [];
        foreach($this->fields as $field) {
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
     * @return SecurityContextInterface
     */
    protected function getSecurityContext()
    {
        return $this->securityContextLink->getService();
    }
}
