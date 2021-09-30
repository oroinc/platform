<?php

namespace Oro\Bundle\OrganizationBundle\Config\Validation;

use Oro\Bundle\EntityConfigBundle\Config\Validation\EntityConfigInterface;
use Symfony\Component\Config\Definition\Builder\NodeBuilder;

/**
 * Provides validations entity config for organization scope.
 */
class OrganizationEntityConfiguration implements EntityConfigInterface
{
    public function getSectionName(): string
    {
        return 'organization';
    }

    public function configure(NodeBuilder $nodeBuilder): void
    {
        $nodeBuilder
            ->arrayNode('applicable')
                ->info('`array` is used to specify which organizations custom entity will be visible to. On the ' .
                    'entity edit page, it is represented with form type oro_type_choice_organization_type, which ' .
                    'provides a selector for organizations (regardless of whether it is activated or not) defined ' .
                    'in the application so that user can select a specific organization(s) or “ALL” organizations.')
                ->example([
                    'all' => true,
                    'selective' => []
                ])
                ->ignoreExtraKeys()
            ->end()
        ;
    }
}
