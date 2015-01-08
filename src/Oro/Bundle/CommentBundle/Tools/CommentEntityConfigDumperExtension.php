<?php

namespace Oro\Bundle\CommentBundle\Tools;

use Oro\Bundle\EntityExtendBundle\Tools\DumperExtensions\AssociationEntityConfigDumperExtension;

class CommentEntityConfigDumperExtension extends AssociationEntityConfigDumperExtension
{
    /**
     * {@inheritdoc}
     */
    protected function getAssociationEntityClass()
    {
        return 'Oro\Bundle\CommentBundle\Entity\Comment';
    }

    /**
     * {@inheritdoc}
     */
    protected function getAssociationScope()
    {
        return 'comment';
    }
}
