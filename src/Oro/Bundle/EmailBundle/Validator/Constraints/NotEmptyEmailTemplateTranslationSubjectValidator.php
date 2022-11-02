<?php

namespace Oro\Bundle\EmailBundle\Validator\Constraints;

use Oro\Bundle\EmailBundle\Entity\EmailTemplateTranslation;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

/**
 * EmailTemplateTranslation should have not empty subject when subjectFallback is false.
 */
class NotEmptyEmailTemplateTranslationSubjectValidator extends ConstraintValidator
{
    /**
     * {@inheritdoc}
     */
    public function validate($value, Constraint $constraint)
    {
        if (!$constraint instanceof NotEmptyEmailTemplateTranslationSubject) {
            throw new UnexpectedTypeException($constraint, NotEmptyEmailTemplateTranslationSubject::class);
        }

        if ($constraint->allowNull && null === $value) {
            return;
        }

        if (!$value instanceof EmailTemplateTranslation) {
            throw new UnexpectedTypeException($value, EmailTemplateTranslation::class);
        }

        if (!$value->getSubject() && !$value->isSubjectFallback()) {
            $this->context->buildViolation($constraint->message)
                ->setParameter('{{ value }}', $this->formatValue($value))
                ->setCode(NotBlank::IS_BLANK_ERROR)
                ->atPath('subject')
                ->addViolation();
        }
    }
}
