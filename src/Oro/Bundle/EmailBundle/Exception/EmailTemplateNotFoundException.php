<?php

namespace Oro\Bundle\EmailBundle\Exception;

use Oro\Bundle\EmailBundle\Model\EmailTemplateCriteria;

/**
 * This exception is thrown when email template could not be found or unambiguously identified by EmailTemplateCriteria.
 */
class EmailTemplateNotFoundException extends EmailTemplateException
{
    public function __construct(EmailTemplateCriteria|string $templateName)
    {
        if ($templateName instanceof EmailTemplateCriteria) {
            $name = $templateName->getName();
            $entityName = $templateName->getEntityName();
        } else {
            $name = $templateName;
            $entityName = '';
        }

        $message = sprintf('Could not found one email template with "%s" name', $name);

        if ($entityName) {
            $message = sprintf('%s for "%s" entity', $message, $entityName);
        } else {
            $message = sprintf('%s and without entity', $message);
        }

        parent::__construct($message);
    }
}
