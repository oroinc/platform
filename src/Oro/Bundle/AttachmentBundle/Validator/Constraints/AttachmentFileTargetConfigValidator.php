<?php

declare(strict_types=1);

namespace Oro\Bundle\AttachmentBundle\Validator\Constraints;

use Oro\Bundle\AttachmentBundle\Entity\Attachment;
use Oro\Bundle\AttachmentBundle\Model\ExternalFile;
use Oro\Bundle\AttachmentBundle\Provider\FileValidationTargetProvider;
use Oro\Bundle\AttachmentBundle\Validator\ConfigFileValidator;
use Symfony\Component\HttpFoundation\File\File as SymfonyFile;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

/**
 * Validates that the uploaded or external file of an attachment is allowed for the attachment target entity.
 */
class AttachmentFileTargetConfigValidator extends ConstraintValidator
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
        if (!$constraint instanceof AttachmentFileTargetConfig) {
            throw new UnexpectedTypeException($constraint, AttachmentFileTargetConfig::class);
        }

        if (!$value instanceof Attachment) {
            return;
        }

        $attachmentFile = $value->getFile()?->getFile();
        if (!$attachmentFile instanceof SymfonyFile && !$attachmentFile instanceof ExternalFile) {
            return;
        }

        $target = $this->validationTargetProvider->getTargetForAttachment($value);
        if (null === $target) {
            return;
        }

        $this->context->getValidator()
            ->inContext($this->context)
            ->atPath(FileValidationTargetProvider::ATTACHMENT_FILE_FIELD_NAME)
            ->validate(
                $attachmentFile,
                $this->configFileValidator->getConstraints($attachmentFile, $target),
                [Constraint::DEFAULT_GROUP]
            );
    }
}
