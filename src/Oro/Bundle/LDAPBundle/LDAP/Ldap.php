<?php
namespace Oro\Bundle\LDAPBundle\LDAP;

use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Zend\Ldap\Ldap as BaseLdap;

class Ldap extends BaseLdap
{
    use TransformsSettings;

    public function __construct(Channel $channel)
    {
        $settings = iterator_to_array($channel->getTransport()->getSettingsBag());
        $mappingSettings = $channel->getMappingSettings();
        $mappingSettings->merge($settings);

        $options = $this->transformSettings($mappingSettings, $this->getTransforms());

        parent::__construct($options);
    }


    /**
     * Returns array of transforms for Ldap settings.
     *
     * @return array
     */
    private function getTransforms()
    {
        return [
            'server_base_dn' => 'baseDn',
            'server_hostname' => 'host',
            'server_port' => 'port',
            'admin_dn' => 'username',
            'admin_password' => 'password',
            'server_encryption' => function ($encryption) {
                return [
                    'useSsl' => $encryption === 'ssl' ? true : false,
                    'useStartTls' => $encryption === 'tls' ? true : false,
                ];
            },
        ];
    }
}
