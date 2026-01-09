<?php

namespace Oro\Bundle\CommentBundle\Tools;

use Oro\Bundle\EntityExtendBundle\Tools\DumperExtensions\AssociationEntityConfigDumperExtension;

/**
 * Extends the association entity configuration dumper to handle comment-specific entity configurations.
 *
 * This extension customizes the entity configuration dumping process for comment associations,
 * specifying that comments use the {@see \Oro\Bundle\CommentBundle\Entity\Comment} entity class
 * and the `comment` scope for configuration. It integrates comment functionality into the
 * entity configuration system, allowing entities to be properly configured with comment support.
 */
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
