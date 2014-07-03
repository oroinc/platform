<?php

namespace Oro\Bundle\AttachmentBundle\Tools;

use Oro\Bundle\AttachmentBundle\EntityConfig\AttachmentScope;
use Oro\Bundle\EntityExtendBundle\Tools\AssociationEntityConfigDumperExtension;

class AttachmentsConfigDumperExtension extends AssociationEntityConfigDumperExtension
{
    /**
     * {@inheritdoc}
     */
    protected function getAssociationEntityClass()
    {
        return AttachmentScope::ATTACHMENT;
    }

    /**
     * {@inheritdoc}
     */
    protected function getAssociationScope()
    {
        return 'attachment';
    }
}
