<?php

namespace Oro\Bundle\NoteBundle\Tools;

use Oro\Bundle\EntityExtendBundle\Tools\GeneratorExtensions\AbstractAssociationEntityGeneratorExtension;

class NoteEntityGeneratorExtension extends AbstractAssociationEntityGeneratorExtension
{
    /**
     * {@inheritdoc}
     */
    public function supports(array $schema)
    {
        return
            $schema['class'] === 'Oro\Bundle\NoteBundle\Entity\Note'
            && parent::supports($schema);
    }
}
