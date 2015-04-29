<?php

namespace Oro\Bundle\LDAPBundle\LDAP;

use FR3D\LdapBundle\Ldap\LdapManager as BaseManager;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;

class LdapManager extends BaseManager
{
    /**
     * Changes baseDn to value given from ConfigManager
     *
     * @param ConfigManager $cm
     */
    public function updateOroConfiguration(ConfigManager $cm)
    {
        $this->params['baseDn'] = $cm->get('oro_ldap.server_base_dn');
    }
}
