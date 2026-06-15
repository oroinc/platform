<?php

declare(strict_types=1);

namespace Oro\Bundle\EmailBundle\Provider;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\EmailBundle\Entity\EmailTemplate;
use Oro\Bundle\EmailBundle\Model\EmailTemplateCriteria;
use Oro\Bundle\EmailBundle\Model\EmailTemplateInterface;

/**
 * Provides metadata for email templates, such as entity name, system/editable/visible flags.
 */
class EmailTemplateMetadataProvider
{
    public const string ENTITY_NAME = 'entityName';
    public const string IS_SYSTEM = 'isSystem';
    public const string IS_EDITABLE = 'isEditable';
    public const string IS_VISIBLE = 'isVisible';

    public function __construct(
        private readonly ManagerRegistry $doctrine,
    ) {
    }

    /**
     * Returns metadata for the given email template.
     *
     * @return array{
     *     entityName: string|null,
     *     isSystem: bool,
     *     isEditable: bool,
     *     isVisible: bool
     * }|null Metadata array if found, or null if template does not exist
     */
    public function getEmailTemplateMetadata(
        EmailTemplateCriteria|EmailTemplateInterface|string $emailTemplate
    ): ?array {
        if ($emailTemplate instanceof EmailTemplateCriteria) {
            $emailTemplateCriteria = $emailTemplate;
        } elseif ($emailTemplate instanceof EmailTemplateInterface) {
            $emailTemplateName = $emailTemplate->getName();
            if ($emailTemplateName === null) {
                return null;
            }
            $emailTemplateCriteria = new EmailTemplateCriteria($emailTemplateName);
        } else {
            $emailTemplateCriteria = new EmailTemplateCriteria($emailTemplate);
        }

        $emailTemplateEntity = $this->doctrine
            ->getRepository(EmailTemplate::class)
            ->findWithLocalizations($emailTemplateCriteria);

        if (!$emailTemplateEntity) {
            return null;
        }

        return [
            self::ENTITY_NAME => $emailTemplateEntity->getEntityName(),
            self::IS_SYSTEM => $emailTemplateEntity->getIsSystem(),
            self::IS_EDITABLE => $emailTemplateEntity->getIsEditable(),
            self::IS_VISIBLE => $emailTemplateEntity->isVisible(),
        ];
    }
}
