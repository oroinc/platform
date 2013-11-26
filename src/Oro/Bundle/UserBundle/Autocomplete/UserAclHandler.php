<?php

namespace Oro\Bundle\UserBundle\Autocomplete;

use Oro\Bundle\FormBundle\Autocomplete\SearchHandlerInterface;

class UserAclHandler implements SearchHandlerInterface
{

    protected $em;

    protected $cache;

    protected $className;

    protected $fields;

    public function __construct($em, $cache, $className, $fields)
    {
        $this->em = $em;
        $this->cache = $cache;
        $this->className = $className;
        $this->fields = $this->fields;
    }

    public function search($query, $page, $perPage)
    {
        $queryBuilder = $this->em->createQueryBuilder()
            ->select(['users.id', 'users.username', 'users.firstName', 'users.middleName', 'users.lastName'])
            ->from('Oro\Bundle\UserBundle\Entity\User', 'users')
            ->where('users.firstName like :searchString')
            ->orWhere('users.lastName like :searchString')
            ->orWhere('users.username like :searchString')
            ->setParameter('searchString', $query . '%');
        $result = $queryBuilder->getQuery()->getResult();

        return [
            'results' => $result,
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

    public function convertItem($user)
    {
        $result['avatar'] = null;

        $imagePath = $this->getPropertyValue('imagePath', $user);
        if ($imagePath) {
            $result['avatar'] = $this->cache->getBrowserPath($imagePath, UserSearchHandler::IMAGINE_AVATAR_FILTER);
        }

        $result['fullName'] = $user['firstName'] . $user['lastName'];

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
} 