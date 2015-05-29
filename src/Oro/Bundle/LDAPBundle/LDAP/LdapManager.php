<?php

namespace Oro\Bundle\LDAPBundle\LDAP;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\ORM\EntityManager;

use FR3D\LdapBundle\Ldap\LdapManager as BaseManager;

use Symfony\Component\Security\Core\User\UserInterface;

use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\LDAPBundle\Model\User;
use Oro\Component\PropertyAccess\PropertyAccessor;

/**
 * @property ZendLdapDriver $driver
 */
class LdapManager extends BaseManager
{
    use TransformsSettings;

    /** @var Registry */
    private $registry;

    /** @var PropertyAccessor|null */
    private $propertyAccessor;

    /** @var Channel */
    private $channel;

    /**
     * @param Registry $registry
     * @param ZendLdapDriver $driver
     * @param type $userManager
     * @param Channel $channel
     */
    public function __construct(Registry $registry, ZendLdapDriver $driver, $userManager, Channel $channel)
    {
        $settings = iterator_to_array($channel->getTransport()->getSettingsBag());
        $mappingSettings = $channel->getMappingSettings();
        $mappingSettings->merge($settings);

        $params = $this->transformSettings($mappingSettings, $this->getTransforms());

        parent::__construct($driver, $userManager, $params);
        $this->registry = $registry;
        $this->channel = $channel;
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

    private function getDn(UserInterface $user)
    {
        $mappings = (array) $user->getLdapMappings();

        if (isset($mappings[$this->channel->getId()])) {
            return $mappings[$this->channel->getId()];
        }

        return false;
    }

    private function setDn(UserInterface $user, $dn)
    {
        $mappings = (array) $user->getLdapMappings();
        $mappings[$this->channel->getId()] = $dn;

        $user->setLdapMappings($mappings);

        return $this;
    }

    /**
     * @param UserInterface $user
     *
     * @return string Dn
     */
    public function save(UserInterface $user)
    {
        $propertyAccessor = $this->getPropertyAccessor();

        $entry = ['objectClass' => [$this->params['export_class']]];
        foreach ($this->params['attributes'] as $attribute) {
            $entry[$attribute['ldap_attr']] = $propertyAccessor->getValue($user, $attribute['user_field']);
        }

        $dn = $this->getDn($user);

        if (!$dn) {
            $dn = $this->createDn($user->getUsername());
        }

        if ($this->driver->exists($dn) && strpos($dn, $user->getUsername()) === false) {
            $newDn = preg_replace('/(?<==).*?(?=,)/', $user->getUsername(), $dn, 1);
            $this->driver->move($dn, $newDn);
            $dn = $newDn;
        }

        $this->setDn($user, $dn);

        $this->registry->getManager()->persist($user);

        return $this->driver->save($dn, $entry);
    }

    /**
     * Checks if user exists.
     *
     * @param UserInterface $user
     *
     * @return bool
     */
    public function exists(UserInterface $user)
    {
        $dn = $this->getDn($user);
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

        $this->setDn($user, $entry['dn']);
        $user->setPassword($originalPassword);

        $roles = $this->findRolesForUser($entry['dn']);
        $this->updateRoles($user, $roles);

        return $result;
    }

    public function bind(UserInterface $user, $password)
    {
        $ldapUser = User::createFromUser($user, $this->channel->getId());

        if ($ldapUser->getDn() == null) {
            return false;
        }

        return parent::bind($ldapUser, $password);
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

    /**
     * Returns array of transforms for settings.
     *
     * @return array
     */
    private function getTransforms()
    {
        return [
            'server_base_dn' => 'baseDn',
            'user_filter' => 'filter',
            'role_filter' => 'role_filter',
            'role_id_attribute' => 'role_id_attribute',
            'role_user_id_attribute' => 'role_user_id_attribute',
            'export_user_base_dn' => 'export_dn',
            'export_user_class' => 'export_class',
            'role_mapping' => [$this, 'transformRoleMapping'],
            'user_mapping' => [$this, 'transformUserMapping'],
        ];
    }

    /**
     * Transforms role mapping to be usable in configuration.
     *
     * @param $roleMapping
     * @return array
     */
    private function transformRoleMapping($roleMapping)
    {
        $roles = [];
        foreach ($roleMapping as $mapping) {
            if (isset($roles[$mapping['ldapName']])) {
                $roles[$mapping['ldapName']] = array_merge($roles[$mapping['ldapName']], $mapping['crmRoles']);
            } else {
                $roles[$mapping['ldapName']] = $mapping['crmRoles'];
            }
        }

        return ['role_mapping' => $roles];
    }

    /**
     * Transforms user mapping to be usable in configuration.
     *
     * @param $mapping
     * @return array
     */
    private function transformUserMapping($mapping)
    {
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
                'ldap_attr' => $ldapAttr,
                'user_method' => sprintf('set%s', ucfirst($userField)),
                'user_field' => $userField,
            ];
        }

        return compact('attributes');
    }
}
