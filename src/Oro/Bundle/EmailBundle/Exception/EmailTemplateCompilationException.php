<?php

namespace Oro\Bundle\EmailBundle\Exception;

use Oro\Bundle\EmailBundle\Model\EmailTemplateCriteria;
use Oro\Bundle\EmailBundle\Model\EmailTemplateInterface;
use Twig\Error\Error;

/**
 * This exception is thrown when email template's could not be compiled using twig.
 */
class EmailTemplateCompilationException extends EmailTemplateException
{
    public function __construct(
        EmailTemplateCriteria|EmailTemplateInterface $object,
        ?Error $previous = null
    ) {
        $message = sprintf('Failed to compile the email template "%s"', $object->getName());

        if ($object->getEntityName()) {
            $message = sprintf('%s for "%s" entity', $message, $object->getEntityName());
        }

        parent::__construct($message, previous: $previous);
    }
}
