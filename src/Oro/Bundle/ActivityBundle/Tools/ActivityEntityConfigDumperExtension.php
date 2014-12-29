<?php

namespace Oro\Bundle\ActivityBundle\Tools;

use Oro\Bundle\ActivityBundle\EntityConfig\ActivityScope;
use Oro\Bundle\EntityExtendBundle\Tools\DumperExtensions\MultipleAssociationEntityConfigDumperExtension;

class ActivityEntityConfigDumperExtension extends MultipleAssociationEntityConfigDumperExtension
{
    /**
     * {@inheritdoc}
     */
    protected function getAssociationScope()
    {
        return 'activity';
    }

    /**
     * {@inheritdoc}
     */
    protected function getAssociationAttributeName()
    {
        return 'activities';
    }

    /**
     * {@inheritdoc}
     */
    protected function getAssociationKind()
    {
        return ActivityScope::ASSOCIATION_KIND;
    }
}
