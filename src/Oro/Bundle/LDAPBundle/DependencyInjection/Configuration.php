<?php

namespace Oro\Bundle\LDAPBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

use Oro\Bundle\ConfigBundle\DependencyInjection\SettingsBuilder;

class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode    = $treeBuilder->root('oro_ldap');

        SettingsBuilder::append($rootNode, [
            'server_enable_login' => [
                'value' => false,
                'type'  => 'boolean',
            ],
            'server_name' => [
                'value' => null,
                'type'  => 'text',
            ],
            'server_hostname' => [
                'value' => '127.0.0.1',
                'type'  => 'text',
            ],
            'server_port' => [
                'value' => null,
                'type'  => 'text',
            ],
            'server_encryption_enabled' => [
                'value' => true,
                'type'  => 'boolean',
            ],
            'server_port' => [
                'value' => 389,
                'type'  => 'integer',
            ],
            'server_protocol_version' => [
                'value' => 3,
                'type'  => 'text',
            ],
            'server_base_dn' => [
                'value' => 'dc=local',
                'type'  => 'text',
            ],
            'admin_dn' => [
                'value' => null,
                'type'  => 'text',
            ],
            'admin_password' => [
                'value' => null,
                'type'  => 'text',
            ],
            'user_filter' => [
                'value' => 'objectClass=inetOrgPerson',
                'type'  => 'text',
            ],
            'user_mapping' => [
                'value' => [],
                'type'  => 'array',
            ],
            'role_filter' => [
                'value' => 'objectClass=groupOfNames',
                'type'  => 'text',
            ],
            'role_id_attribute' => [
                'value' => 'cn',
                'type'  => 'text',
            ],
            'role_user_id_attribute' => [
                'value' => 'member',
                'type'  => 'text',
            ],
            'role_mapping' => [
                'value' => [],
                'type'  => 'text',
            ],
        ]);

        return $treeBuilder;
    }
}
