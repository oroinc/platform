<?php

namespace Oro\Bundle\EmailBundle\Exception;

use Oro\Bundle\EmailBundle\Model\EmailTemplateCriteria;
use Twig\Error\Error;

/**
 * This exception is thrown when email template's could not be compiled using twig.
 */
class EmailTemplateCompilationException extends EmailTemplateException
{
    public function __construct(EmailTemplateCriteria $criteria, Error $previous = null)
    {
        $message = sprintf('Could not compile one email template with "%s" name', $criteria->getName());

        if ($criteria->getEntityName()) {
            $message = sprintf('%s for "%s" entity', $message, $criteria->getEntityName());
        }

        parent::__construct($message, previous: $previous);
    }
}
