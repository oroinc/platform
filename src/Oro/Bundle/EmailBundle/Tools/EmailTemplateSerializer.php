<?php

declare(strict_types=1);

namespace Oro\Bundle\EmailBundle\Tools;

use Oro\Bundle\EmailBundle\Entity\EmailTemplate;

/**
 * Serializes email template entity for {@see \Oro\Bundle\EmailBundle\Controller\Api\Rest\EmailTemplateController}.
 */
class EmailTemplateSerializer
{
    public function serialize(EmailTemplate $emailTemplate): array
    {
        return [
            'id' => $emailTemplate->getId(),
            'name' => $emailTemplate->getName(),
            'is_system' => $emailTemplate->getIsSystem(),
            'is_editable' => $emailTemplate->getIsEditable(),
            'parent' => $emailTemplate->getParent(),
            'subject' => $emailTemplate->getSubject(),
            'content' => $emailTemplate->getContent(),
            'entity_name' => $emailTemplate->getEntityName(),
            'type' => $emailTemplate->getType(),
        ];
    }
}
