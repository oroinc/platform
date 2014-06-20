<?php

namespace Oro\Bundle\ActivityBundle\Tools;

use Oro\Bundle\EntityExtendBundle\Tools\MultipleAssociationExtendConfigDumperExtension;

class ActivityExtendConfigDumperExtension extends MultipleAssociationExtendConfigDumperExtension
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
}
