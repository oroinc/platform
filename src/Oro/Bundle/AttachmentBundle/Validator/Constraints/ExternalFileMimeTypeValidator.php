<?php

namespace Oro\Bundle\AttachmentBundle\Validator\Constraints;

use Oro\Bundle\AttachmentBundle\Model\ExternalFile;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

/**
 * Validates the mime type of {@see ExternalFile}.
 */
class ExternalFileMimeTypeValidator extends ConstraintValidator
{
    /**
     * @param ExternalFile|string|null $value ExternalFile model or MIME type
     * @param Constraint $constraint
     */
    #[\Override]
    public function validate($value, Constraint $constraint): void
    {
        if (!$constraint instanceof ExternalFileMimeType) {
            throw new UnexpectedTypeException($constraint, ExternalFileMimeType::class);
        }

        if (null === $value) {
            return;
        }

        if ($value instanceof ExternalFile) {
            $value = $value->getMimeType();
        }

        if (!is_scalar($value)) {
            throw new UnexpectedValueException($value, 'scalar');
        }

        if (!$this->isMimeTypeAllowed(strtolower($value), $constraint->mimeTypes)) {
            $this->context
                ->buildViolation($constraint->message)
                ->setParameter('{{ type }}', $this->formatValue($value))
                ->setParameter('{{ types }}', $this->formatValues($constraint->mimeTypes))
                ->setCode(ExternalFileMimeType::INVALID_MIME_TYPE_ERROR)
                ->addViolation();
        }
    }

    private function isMimeTypeAllowed(string $mimeType, array $allowedMimeTypes): bool
    {
        foreach ($allowedMimeTypes as $allowedMimeType) {
            if ($mimeType === $allowedMimeType) {
                return true;
            }

            $isGroupWildcard = \strlen($allowedMimeType) > 2 && str_ends_with($allowedMimeType, '/*');
            if ($isGroupWildcard && str_starts_with($mimeType, substr($allowedMimeType, 0, -1))) {
                return true;
            }
        }

        return false;
    }
}
