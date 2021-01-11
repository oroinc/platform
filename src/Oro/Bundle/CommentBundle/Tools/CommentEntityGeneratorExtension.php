<?php
declare(strict_types=1);

namespace Oro\Bundle\CommentBundle\Tools;

use Oro\Bundle\CommentBundle\Entity\Comment;
use Oro\Bundle\EntityExtendBundle\Tools\GeneratorExtensions\AbstractAssociationEntityGeneratorExtension;

/**
 * Generates PHP code for many-to-one comment association.
 */
class CommentEntityGeneratorExtension extends AbstractAssociationEntityGeneratorExtension
{
    public function supports(array $schema): bool
    {
        return
            $schema['class'] === Comment::class
            && parent::supports($schema);
    }
}
