<?php

namespace Oro\Bundle\LDAPBundle\LDAP;

use FR3D\LdapBundle\Ldap\LdapManager as BaseManager;

use Symfony\Component\Security\Core\User\UserInterface;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;

class LdapManager extends BaseManager
{
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

        return $result;
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
}
