<?php
namespace Oro\Bundle\LDAPBundle\Provider\Transport;

use Symfony\Component\HttpFoundation\ParameterBag;

use Oro\Bundle\IntegrationBundle\Entity\Transport;
use Oro\Bundle\IntegrationBundle\Provider\TransportInterface;

class LdapTransport implements TransportInterface
{
    /** @var ParameterBag */
    protected $settings;

    /**
     * {@inheritdoc}
     */
    public function init(Transport $transportEntity)
    {
        $this->settings = $transportEntity->getSettingsBag();
    }

    /**
     * {@inheritdoc}
     */
    public function getLabel()
    {
        return 'oro.ldap.transport.ldap.label';
    }

    /**
     * {@inheritdoc}
     */
    public function getSettingsFormType()
    {
        return "oro_ldap_ldap_transport_setting_form_type";
    }

    /**
     * {@inheritdoc}
     */
    public function getSettingsEntityFQCN()
    {
        return 'Oro\Bundle\LDAPBundle\Entity\LdapTransport';
    }
}
