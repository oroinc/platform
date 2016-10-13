<?php

namespace Oro\Bundle\InstallerBundle\CacheWarmer;

class NamespaceMigrationProvider implements NamespaceMigrationProviderInterface
{
    /** @var string[] */
    protected $additionConfig
        = [
            'OroB2B\Bundle\AccountBundle' => 'OroB2B\Bundle\CustomerBundle',
            'OroB2BAccountBundle'                                                    => 'OroB2BCustomerBundle',
            'OroPro\Bundle\SecurityBundle\Migrations\Data\ORM\SetShareGridConfig'    =>
                'Oro\Bundle\SecurityProBundle\Migrations\Data\ORM\SetShareGridConfig',
            'OroCRMPro\Bundle\SecurityBundle\Migrations\Data\ORM\SetShareGridConfig' =>
                'Oro\Bundle\SecurityCRMProBundle\Migrations\Data\ORM\SetShareGridConfig',
            'OroProOrganizationBundle'                                               => 'OroOrganizationProBundle',
            'OroProSecurityBundle'                                                   => 'OroSecurityProBundle',
            'OroCRMTaskBridgeBundle'                                                 => 'OroTaskCRMBridgeBundle',
            'OroCRMCallBridgeBundle'                                                 => 'OroCallCRMBridgeBundle',
            'OroProUserBundle'                                                       => 'OroUserProBundle',
            'OroCRMPro'                                                              => 'Oro',
            'OroCRM'                                                                 => 'Oro',
            'OroPro'                                                                 => 'Oro',
            'orocrm'                                                                 => 'oro'
        ];

    /**
     * (@inheritdoc}
     */
    public function getConfig()
    {
        return $this->additionConfig;
    }
}
