<?php

declare(strict_types=1);

namespace Oro\Bundle\AttachmentBundle\Validator\Constraints;

use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\AttachmentBundle\Model\ExternalFile;
use Oro\Bundle\AttachmentBundle\Provider\FileValidationTargetProvider;
use Oro\Bundle\AttachmentBundle\Validator\ConfigFileValidator;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

/**
 * Validates that the external file of a file is allowed for its parent entity field,
 * or by the system-wide restrictions when the file has no parent entity.
 */
class ExternalFileParentFieldConfigValidator extends ConstraintValidator
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
        if (!$constraint instanceof ExternalFileParentFieldConfig) {
            throw new UnexpectedTypeException($constraint, ExternalFileParentFieldConfig::class);
        }

        $file = $this->context->getObject();
        if (!$file instanceof File) {
            return;
        }

        $externalFile = $file->getFile();
        if (!$externalFile instanceof ExternalFile) {
            return;
        }

        if ($this->validationTargetProvider->isSubmittedWithAttachment($file)) {
            return;
        }

        $target = $this->validationTargetProvider->getTargetForFile($file);
        $this->context->getValidator()
            ->inContext($this->context)
            ->validate(
                $externalFile,
                $this->configFileValidator->getConstraints($externalFile, $target),
                [Constraint::DEFAULT_GROUP]
            );
    }
}
