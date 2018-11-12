<?php

namespace Oro\Bundle\EmailBundle\Exception;

use Oro\Bundle\EmailBundle\Model\EmailTemplateCriteria;

/**
 * This exception is thrown when email template could not be found or unambiguously identified by EmailTemplateCriteria.
 */
class EmailTemplateNotFoundException extends EmailTemplateException
{
    /**
     * @param EmailTemplateCriteria $criteria
     */
    public function __construct(EmailTemplateCriteria $criteria)
    {
        $message = sprintf('Could not found one email template with "%s" name', $criteria->getName());

        if ($criteria->getEntityName()) {
            $message = sprintf('%s for "%s" entity', $message, $criteria->getEntityName());
        } else {
            $message = sprintf('%s and without entity', $message);
        }

        parent::__construct($message);
    }
}
