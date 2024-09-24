<?php

namespace Oro\Bundle\AttachmentBundle\Tools;

use Oro\Bundle\AttachmentBundle\EntityConfig\AttachmentScope;
use Oro\Bundle\EntityExtendBundle\Tools\DumperExtensions\AssociationEntityConfigDumperExtension;

class AttachmentConfigDumperExtension extends AssociationEntityConfigDumperExtension
{
    #[\Override]
    protected function getAssociationEntityClass()
    {
        return AttachmentScope::ATTACHMENT;
    }

    #[\Override]
    protected function getAssociationScope()
    {
        return 'attachment';
    }
}
