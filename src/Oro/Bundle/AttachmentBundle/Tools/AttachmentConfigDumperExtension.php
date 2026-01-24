<?php

namespace Oro\Bundle\AttachmentBundle\Tools;

use Oro\Bundle\AttachmentBundle\EntityConfig\AttachmentScope;
use Oro\Bundle\EntityExtendBundle\Tools\DumperExtensions\AssociationEntityConfigDumperExtension;

/**
 * Extends entity configuration dumping to include attachment association metadata.
 *
 * This extension customizes the entity configuration dumping process to properly handle
 * attachment-related associations. It specifies the attachment entity class and scope
 * used by the attachment system, allowing the configuration dumper to generate correct
 * metadata for entities that have attachment associations configured. This is essential
 * for the entity extend system to properly manage attachment relationships.
 */
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
