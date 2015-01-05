<?php

namespace Oro\Bundle\CommentBundle\Tools;

use Oro\Bundle\EntityExtendBundle\Tools\GeneratorExtensions\AbstractAssociationEntityGeneratorExtension;

class CommentEntityGeneratorExtension extends AbstractAssociationEntityGeneratorExtension
{
    /**
     * {@inheritdoc}
     */
    public function supports(array $schema)
    {
        return
            $schema['class'] === 'Oro\Bundle\CommentBundle\Entity\Comment'
            && parent::supports($schema);
    }
}
