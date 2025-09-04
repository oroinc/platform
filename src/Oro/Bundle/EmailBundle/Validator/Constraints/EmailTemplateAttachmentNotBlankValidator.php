<?php

declare(strict_types=1);

namespace Oro\Bundle\EmailBundle\Validator\Constraints;

use Oro\Bundle\EmailBundle\Entity\EmailTemplateAttachment;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

/**
 * Validates that only one field of {@see EmailTemplateAttachment} is not blank.
 */
class EmailTemplateAttachmentNotBlankValidator extends ConstraintValidator
{
    #[\Override]
    public function validate($value, Constraint $constraint): void
    {
        if (!$constraint instanceof EmailTemplateAttachmentNotBlank) {
            throw new UnexpectedTypeException($constraint, EmailTemplateAttachmentNotBlank::class);
        }

        if (!$value instanceof EmailTemplateAttachment) {
            throw new UnexpectedTypeException($value, EmailTemplateAttachment::class);
        }

        $notBlankFieldsCount = $this->countNotBlankFields($value);

        if (!$notBlankFieldsCount || $notBlankFieldsCount > 1) {
            $this->context
                ->buildViolation($constraint->message)
                ->setCode(EmailTemplateAttachmentNotBlank::ONLY_ONE_FIELD_NOT_BLANK)
                ->addViolation();
        }
    }

    private function countNotBlankFields(EmailTemplateAttachment $attachment): int
    {
        $count = 0;

        if ($this->isFileNotBlank($attachment->getFile())) {
            $count++;
        }

        if (!empty($attachment->getFilePlaceholder())) {
            $count++;
        }

        return $count;
    }

    private function isFileNotBlank($file): bool
    {
        return $file !== null && !$file->isEmptyFile() && ($file->getFile() || $file->getFilename());
    }
}
