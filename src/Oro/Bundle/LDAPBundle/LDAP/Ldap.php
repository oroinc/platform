<?php

namespace Oro\Bundle\LDAPBundle\LDAP;

use Zend\Ldap\Ldap as BaseLdap;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;

class Ldap extends BaseLdap
{
    /**
     * Changes options to values given from ConfigManager
     *
     * @param ConfigManager $cm
     */
    public function updateOroConfiguration(ConfigManager $cm)
    {
        $this->options['baseDn'] = $cm->get('oro_ldap.server_base_dn');
        $this->options['host']   = $cm->get('oro_ldap.server_hostname');
        $this->options['port']   = $cm->get('oro_ldap.server_port');

        $this->options['username'] = $cm->get('oro_ldap.admin_dn');
        $this->options['password'] = $cm->get('oro_ldap.admin_password');
    }
}
