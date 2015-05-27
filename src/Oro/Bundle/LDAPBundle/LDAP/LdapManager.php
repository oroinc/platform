<?php

namespace Oro\Bundle\LDAPBundle\LDAP;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\ORM\EntityManager;

use FR3D\LdapBundle\Ldap\LdapManager as BaseManager;

use Symfony\Component\Security\Core\User\UserInterface;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\LDAPBundle\LDAP\ZendLdapDriver;
use Oro\Component\PropertyAccess\PropertyAccessor;

/**
 * @property ZendLdapDriver $driver
 */
class LdapManager extends BaseManager
{
    /** @var Registry */
    private $registry;

    /** @var PropertyAccessor|null */
    private $propertyAccessor;

    /**
     * @param Registry $registry
     * @param ZendLdapDriver $driver
     * @param type $userManager
     * @param array $params
     */
    public function __construct(Registry $registry, ZendLdapDriver $driver, $userManager, array $params)
    {
        parent::__construct($driver, $userManager, $params);
        $this->registry = $registry;
    }

    /**
     * Returns all users from ldap.
     *
     * @return array
     */
    public function findUsers()
    {
        $attributes = $this->params['attributes'];
        $userAttributes = [];
        foreach ($attributes as $attribute) {
            $userAttributes[] = $attribute['ldap_attr'];
        }

        $users = $this->driver->search(
            $this->params['baseDn'],
            $this->params['filter'],
            $userAttributes
        );
        unset($users['count']);
        return $users;
    }

    /**
     * @param string $userDn
     *
     * @return array
     */
    private function findRolesForUser($userDn)
    {
        $roles = $this->driver->search(
            $this->params['baseDn'],
            sprintf(
                '(&(%s)(%s=%s))',
                $this->params['role_filter'],
                $this->params['role_user_id_attribute'],
                $userDn
            ),
            [$this->params['role_id_attribute']]
        );

        unset($roles['count']);

        return $roles;
    }

    /**
     * @return string
     */
    public function getUsernameAttr()
    {
        return $this->ldapUsernameAttr;
    }

    /**
     * @param UserInterface $user
     *
     * @param string $dn
     * @return string Dn
     */
    public function save(UserInterface $user, $dn = null)
    {
        $propertyAccessor = $this->getPropertyAccessor();

        $entry = ['objectClass' => [$this->params['export_class']]];
        foreach ($this->params['attributes'] as $attribute) {
            $entry[$attribute['ldap_attr']] = $propertyAccessor->getValue($user, $attribute['user_field']);
        }

        if (!$dn) {
            $dn = $this->createDn($user->getUsername());
        }

        if ($this->driver->exists($dn) && strpos($dn, $user->getUsername()) === false) {
            $newDn = preg_replace('/(?<==).*?(?=,)/', $user->getUsername(), $dn, 1);
            $this->driver->move($dn, $newDn);
            $dn = $newDn;
        }

        $this->driver->save($dn, $entry);

        return $dn;
    }

    /**
     * Checks if user exists.
     *
     * @param UserInterface $user
     * @param string $dn Optional Dn of user.
     *
     * @return bool
     */
    public function exists(UserInterface $user, $dn = null)
    {
        return $this->driver->exists($dn ? $dn : $this->createDn($user->getUsername()));
    }

    /**
     * Returns new Dn for export.
     *
     * @param string $username
     *
     * @return string
     */
    private function createDn($username)
    {
        return sprintf('%s=%s,%s', $this->ldapUsernameAttr, $username, $this->params['export_dn']);
    }

    /**
     * {@inheritdoc}
     */
    public function hydrate(UserInterface $user, array $entry)
    {
        $originalPassword = $user->getPassword();

        $result = parent::hydrate($user, $entry);

        $user->setPassword($originalPassword);

        $roles = $this->findRolesForUser($entry['dn']);
        $this->updateRoles($user, $roles);

        return $result;
    }

    /**
     * @param UserInterface $user
     * @param array $entries
     */
    private function updateRoles(UserInterface $user, array $entries)
    {
        $ldapRoles = [];
        foreach ($entries as $entry) {
            if (!array_key_exists($this->params['role_id_attribute'], $entry)) {
                continue;
            }

            $ldapValue = $entry[$this->params['role_id_attribute']];
            $value = null;

            if (!array_key_exists('count', $ldapValue) ||  $ldapValue['count'] == 1) {
                $value = $ldapValue[0];
            } else {
                $value = array_slice($ldapValue, 1);
            }

            if ($value) {
                $ldapRoles[] = $value;
            }
        }

        $roles = [];
        foreach ($ldapRoles as $ldapRole) {
            if (!isset($this->params['role_mapping'][$ldapRole])) {
                continue;
            }

            $roles = array_merge($roles, $this->params['role_mapping'][$ldapRole]);
        }

        $uniqueRoles = array_unique($roles);
        if (!$uniqueRoles) {
            return;
        }

        $em = $this->getRoleEntityManager();
        $roleReferences = [];
        foreach ($uniqueRoles as $role) {
            $roleReferences[] = $em->getReference('Oro\Bundle\UserBundle\Entity\Role', $role);
        }

        array_map([$user, 'addRole'], $roleReferences);
    }

    /**
     * @return EntityManager
     */
    private function getUserEntityManager()
    {
        return $this->registry->getManagerForClass('OroUserBundle:User');
    }

    /**
     * @return EntityManager
     */
    private function getRoleEntityManager()
    {
        return $this->registry->getManagerForClass('OroUserBundle:Role');
    }

    /**
     * @return PropertyAccessor
     */
    private function getPropertyAccessor()
    {
        if (!$this->propertyAccessor) {
            $this->propertyAccessor = new PropertyAccessor();
        }

        return $this->propertyAccessor;
    }

    /**
     * @return array
     */
    public function getSynchronizedFields()
    {
        return array_map(function ($attribute) {
            return $attribute['user_field'];
        }, $this->params['attributes']);
    }
}
