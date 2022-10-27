<?php

namespace Oro\Bundle\AttachmentBundle\Validator\Constraints;

use Oro\Bundle\AttachmentBundle\Tools\MimeTypesConverter;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

/**
 * The validator for MimeType constraint that can be used to check allowed MIME types.
 * The list of MIME types can be an array or a string contains MIME types delimited by linefeed (\n) symbol.
 */
class MimeTypeValidator extends ConstraintValidator
{
    /** @var string[] */
    private $allowedFileMimeTypes;

    /** @var string[] */
    private $allowedImageMimeTypes;

    /**
     * @param string[] $allowedFileMimeTypes
     * @param string[] $allowedImageMimeTypes
     */
    public function __construct(array $allowedFileMimeTypes, array $allowedImageMimeTypes)
    {
        $this->allowedFileMimeTypes = $allowedFileMimeTypes;
        $this->allowedImageMimeTypes = $allowedImageMimeTypes;
    }

    /**
     * {@inheritdoc}
     */
    public function validate($value, Constraint $constraint)
    {
        if (!$constraint instanceof MimeType) {
            throw new UnexpectedTypeException($constraint, MimeType::class);
        }

        if (null === $value) {
            return;
        }

        if (is_string($value)) {
            $value = MimeTypesConverter::convertToArray($value);
        } elseif (!is_array($value)) {
            throw new UnexpectedTypeException($value, 'array or string');
        }

        $notAllowedMimeTypes = array_diff($value, $this->getAllowedMimeTypes($constraint->type));
        if (!empty($notAllowedMimeTypes)) {
            $this->context->buildViolation($constraint->message)
                ->setParameters(['{{ notAllowedMimeTypes }}' => implode(', ', $notAllowedMimeTypes)])
                ->setPlural(count($notAllowedMimeTypes))
                ->addViolation();
        }
    }

    /**
     * @param string $constraintType
     *
     * @return string[]
     */
    private function getAllowedMimeTypes(string $constraintType): array
    {
        if ('file' === $constraintType) {
            return $this->allowedFileMimeTypes;
        }

        if ('image' === $constraintType) {
            return $this->allowedImageMimeTypes;
        }

        return array_unique(array_merge($this->allowedFileMimeTypes, $this->allowedImageMimeTypes));
    }
}
