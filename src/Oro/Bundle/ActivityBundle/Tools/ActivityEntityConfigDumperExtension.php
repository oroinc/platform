<?php

namespace Oro\Bundle\ActivityBundle\Tools;

use Oro\Bundle\ActivityBundle\EntityConfig\ActivityScope;
use Oro\Bundle\EntityExtendBundle\Tools\DumperExtensions\MultipleAssociationEntityConfigDumperExtension;

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
