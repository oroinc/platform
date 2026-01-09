<?php

namespace Oro\Bundle\ActivityBundle\Tools;

use Oro\Bundle\ActivityBundle\EntityConfig\ActivityScope;
use Oro\Bundle\EntityExtendBundle\Tools\DumperExtensions\MultipleAssociationEntityConfigDumperExtension;

/**
 * Extends entity configuration dumping to handle activity-specific association metadata.
 *
 * This extension customizes the entity configuration dumping process to properly handle
 * activity associations. It specifies that activity associations should be dumped under
 * the `activity` scope with the `activities` attribute name, and uses the activity-specific
 * association kind. This ensures that activity metadata is correctly generated and persisted
 * in the extended entity configuration.
 */
class ActivityEntityConfigDumperExtension extends MultipleAssociationEntityConfigDumperExtension
{
    #[\Override]
    protected function getAssociationScope()
    {
        return 'activity';
    }

    #[\Override]
    protected function getAssociationAttributeName()
    {
        return 'activities';
    }

    #[\Override]
    protected function getAssociationKind()
    {
        return ActivityScope::ASSOCIATION_KIND;
    }
}
