<?php

namespace Oro\Bundle\LDAPBundle\LDAP;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\ORM\EntityManager;
use FR3D\LdapBundle\Driver\LdapDriverInterface;
use FR3D\LdapBundle\Ldap\LdapManager as BaseManager;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Symfony\Component\Security\Core\User\UserInterface;

class LdapManager extends BaseManager
{
    /** @var Registry */
    private $registry;

    /**
     * @param Registry $registry
     * @param LdapDriverInterface $driver
     * @param type $userManager
     * @param array $params
     */
    public function __construct(Registry $registry, LdapDriverInterface $driver, $userManager, array $params)
    {
        parent::__construct($driver, $userManager, $params);
        $this->registry = $registry;
    }

    /**
     * @return array
     */
    public function findUsers()
    {
        return $this->driver->search(
            $this->params['baseDn'],
            $this->params['filter']
        );
    }

    /**
     * @param string $userDn
     *
     * @return array
     */
    private function findRolesForUser($userDn)
    {
        return $this->driver->search(
            $this->params['baseDn'],
            sprintf(
                '(&(%s)(%s=%s))',
                $this->params['role_filter'],
                $this->params['role_user_id_attribute'],
                $userDn
            )
        );
    }

    /**
     * @return string
     */
    public function getUsernameAttr()
    {
        return $this->ldapUsernameAttr;
    }

    /**
     * {@inheritdoc}
     */
    public function hydrate(UserInterface $user, array $entry)
    {
        $originalPassword = $user->getPassword();

        $result = parent::hydrate($user, $entry);

        $user->setPassword($originalPassword);
        $user->setDn($entry['dn']);

        $roles = $this->findRolesForUser($user->getDn());
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
     * Changes params to values given from ConfigManager
     *
     * @param ConfigManager $cm
     */
    public function updateOroConfiguration(ConfigManager $cm)
    {
        $this->params['baseDn'] = $cm->get('oro_ldap.server_base_dn');
        $this->params['filter'] = $cm->get('oro_ldap.user_filter');

        $this->params['role_filter']            = $cm->get('oro_ldap.role_filter');
        $this->params['role_id_attribute']      = $cm->get('oro_ldap.role_id_attribute');
        $this->params['role_user_id_attribute'] = $cm->get('oro_ldap.role_user_id_attribute');

        $roleMapping = $cm->get('oro_ldap.role_mapping');
        $roles = [];
        foreach ($roleMapping as $mapping) {
            if (isset($roles[$mapping['ldapName']])) {
                $roles[$mapping['ldapName']] = array_merge($roles[$mapping['ldapName']], $mapping['crmRoles']);
            } else {
                $roles[$mapping['ldapName']] = $mapping['crmRoles'];
            }
        }

        $this->params['role_mapping'] = $roles;

        $attributes = $this->getAttributes($cm);
        if ($attributes) {
            $this->params['attributes'] = $attributes;
            $this->ldapAttributes = [];
            foreach ($this->params['attributes'] as $attr) {
                $this->ldapAttributes[] = $attr['ldap_attr'];
            }

            $this->ldapUsernameAttr = $this->ldapAttributes[0];
        }
    }

    /**
     * @param ConfigManager $cm
     *
     * @return array
     */
    private function getAttributes(ConfigManager $cm)
    {
        $mapping = $cm->get('oro_ldap.user_mapping');
        $definedMapping = array_filter($mapping, 'strlen');
        if (!isset($definedMapping['username'])) {
            return [];
        }

        $username = $definedMapping['username'];
        unset($definedMapping['username']);

        $sortedMapping = array_merge(['username' => $username], $definedMapping);
        $attributes = [];
        foreach ($sortedMapping as $userField => $ldapAttr) {
            $attributes[] = [
                'ldap_attr'   => $ldapAttr,
                'user_method' => sprintf('set%s', ucfirst($userField)),
            ];
        }

        return $attributes;
    }

    /**
     * @return EntityManager
     */
    private function getRoleEntityManager()
    {
        return $this->registry->getManagerForClass('OroUserBundle:Role');
    }
}
