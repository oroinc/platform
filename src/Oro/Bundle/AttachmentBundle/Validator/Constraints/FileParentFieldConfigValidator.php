<?php

declare(strict_types=1);

namespace Oro\Bundle\AttachmentBundle\Validator\Constraints;

use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\AttachmentBundle\Provider\FileValidationTargetProvider;
use Oro\Bundle\AttachmentBundle\Validator\ConfigFileValidator;
use Symfony\Component\HttpFoundation\File\File as SymfonyFile;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

/**
 * Validates that the uploaded content of a file is allowed for the parent entity field specified in the file,
 * or by the system-wide restrictions when the file has no parent entity.
 */
class FileParentFieldConfigValidator extends ConstraintValidator
{
    public function __construct(
        private readonly ConfigFileValidator $configFileValidator,
        private readonly FileValidationTargetProvider $validationTargetProvider,
    ) {
    }

    /**
     * @throws UnexpectedTypeException if the constraint is not supported
     */
    #[\Override]
    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof FileParentFieldConfig) {
            throw new UnexpectedTypeException($constraint, FileParentFieldConfig::class);
        }

        $file = $this->context->getObject();
        if (!$value instanceof SymfonyFile || !$file instanceof File) {
            return;
        }

        if ($this->validationTargetProvider->isSubmittedWithAttachment($file)) {
            return;
        }

        $target = $this->validationTargetProvider->getTargetForFile($file);
        $this->context->getValidator()
            ->inContext($this->context)
            ->validate(
                $value,
                $this->configFileValidator->getConstraints($value, $target),
                [Constraint::DEFAULT_GROUP]
            );
    }
}
