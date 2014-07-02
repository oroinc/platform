<?php

namespace Oro\Bundle\AttachmentBundle\Tools;

use Oro\Bundle\AttachmentBundle\EntityConfig\AttachmentScope;
use Oro\Bundle\EntityExtendBundle\Tools\AbstractAssociationEntityGeneratorExtension;

class AttachmentsGeneratorExtension extends AbstractAssociationEntityGeneratorExtension
{
    /**
     * {@inheritdoc}
     */
    public function supports(array $schema)
    {
        return
            $schema['class'] === AttachmentScope::ATTACHMENT_ENTITY
            && parent::supports($schema);
    }

    /**
     * {@inheritdoc}
     */
    protected function getAssociationType()
    {
        return 'manyToMany';
    }
}
