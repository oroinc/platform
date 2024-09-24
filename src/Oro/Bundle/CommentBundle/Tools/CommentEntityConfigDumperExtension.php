<?php

namespace Oro\Bundle\CommentBundle\Tools;

use Oro\Bundle\EntityExtendBundle\Tools\DumperExtensions\AssociationEntityConfigDumperExtension;

class CommentEntityConfigDumperExtension extends AssociationEntityConfigDumperExtension
{
    #[\Override]
    protected function getAssociationEntityClass()
    {
        return 'Oro\Bundle\CommentBundle\Entity\Comment';
    }

    #[\Override]
    protected function getAssociationScope()
    {
        return 'comment';
    }
}
