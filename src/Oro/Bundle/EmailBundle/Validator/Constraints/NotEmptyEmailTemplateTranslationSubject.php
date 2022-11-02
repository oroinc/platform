<?php

namespace Oro\Bundle\EmailBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * EmailTemplateTranslation should have not empty subject when subjectFallback is false.
 */
class NotEmptyEmailTemplateTranslationSubject extends NotBlank
{
    /**
     * {@inheritdoc}
     */
    public function getTargets()
    {
        return self::CLASS_CONSTRAINT;
    }
}
