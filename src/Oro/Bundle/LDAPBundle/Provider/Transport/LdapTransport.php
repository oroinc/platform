<?php
/**
 * LdapTransport.php
 *
 * Project: crm-enterprise-dev
 * Author: Jakub Babiak <jakub@babiak.cz>
 * Created: 13/05/15 11:00
 */

namespace Oro\Bundle\LDAPBundle\Provider\Transport;

use Oro\Bundle\IntegrationBundle\Entity\Transport;
use Oro\Bundle\IntegrationBundle\Provider\TransportInterface;
use Symfony\Component\HttpFoundation\ParameterBag;

class LdapTransport implements TransportInterface
{
    /**
     * @var ParameterBag
     */
    protected $settings;

    /**
     * @param \Oro\Bundle\IntegrationBundle\Entity\Transport $transportEntity
     */
    public function init(Transport $transportEntity)
    {
        $this->settings = $transportEntity->getSettingsBag();
    }

    /**
     * Returns label for UI
     *
     * @return string
     */
    public function getLabel()
    {
        return 'oro.ldap.transport.ldap.label';
    }

    /**
     * Returns form type name needed to setup transport
     *
     * @return string
     */
    public function getSettingsFormType()
    {
        return "oro_ldap_ldap_transport_setting_form_type";
    }

    /**
     * Returns entity name needed to store transport settings
     *
     * @return string
     */
    public function getSettingsEntityFQCN()
    {
        return 'Oro\Bundle\LDAPBundle\Entity\LdapTransport';
    }
}