<?php

namespace Oro\Bundle\SecurityBundle\EntityConfig;

use Oro\Bundle\EntityConfigBundle\EntityConfig\EntityConfigInterface;
use Symfony\Component\Config\Definition\Builder\NodeBuilder;

/**
 * Provides validations entity config for security scope.
 */
class SecurityEntityConfiguration implements EntityConfigInterface
{
    public function getSectionName(): string
    {
        return 'security';
    }

    public function configure(NodeBuilder $nodeBuilder): void
    {
        $nodeBuilder
            ->scalarNode('type')
                ->info('`string` is a type of security. In most cases “ACL”.')
            ->end()
            ->scalarNode('permissions')
                ->info('`string` is used to specify the access list for the entity. Example: VIEW;CREATE;EDIT;DELETE.')
            ->end()
            ->scalarNode('group_name')
                ->info('`string` is used to group entities by applications.')
            ->end()
            ->booleanNode('field_acl_supported')
                ->info('`boolean` enable this attribute to prepare the system to check access to the entity ' .
                'fields. For more information, see `Enable Support of Field ACL for an Entity`(' .
                'https://doc.oroinc.com/backend/security/field-acl/#backend-security-bundle-field-acl-enable-support).')
                ->defaultFalse()
            ->end()
            ->booleanNode('field_acl_enabled')
                ->info('`boolean` enable the field ACL.')
                ->defaultFalse()
            ->end()
            ->booleanNode('show_restricted_fields')
                ->info('`boolean` enable to show the restricted field.')
                ->defaultFalse()
            ->end()
            ->scalarNode('category')
                ->info('`string` is used to categorize an entity.')
            ->end()
            ->scalarNode('share_grid')
                ->info('`string` the share grid name.')
            ->end()
        ;
    }
}
