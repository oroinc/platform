<?php

namespace Oro\Bundle\AttachmentBundle\EntityConfig;

/**
 * Defines scope constants for attachment-related entity configuration.
 *
 * This class provides centralized constants that identify the core attachment entities
 * used throughout the attachment bundle. These constants are used in entity configuration
 * to establish relationships and associations between entities and the attachment system.
 */
class AttachmentScope
{
    public const ATTACHMENT_ENTITY = 'Oro\Bundle\AttachmentBundle\Entity\File';
    public const ATTACHMENT        = 'Oro\Bundle\AttachmentBundle\Entity\Attachment';
}
