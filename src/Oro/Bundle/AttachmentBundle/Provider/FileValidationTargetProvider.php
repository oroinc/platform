<?php

declare(strict_types=1);

namespace Oro\Bundle\AttachmentBundle\Provider;

use Oro\Bundle\AttachmentBundle\Entity\Attachment;
use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\AttachmentBundle\Model\FileValidationTarget;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;

/**
 * Resolves the entity and field whose upload restrictions apply to a file.
 */
class FileValidationTargetProvider
{
    public const string ATTACHMENT_FILE_FIELD_NAME = 'file';

    public function __construct(
        private readonly DoctrineHelper $doctrineHelper,
        private readonly FileValidationTargetRegistry $targetRegistry
    ) {
    }

    /**
     * A file submitted together with an attachment is validated by the attachment,
     * check isSubmittedWithAttachment() before using the target of such a file.
     */
    public function getTargetForFile(File $file): FileValidationTarget
    {
        $registeredTarget = $this->targetRegistry->get($file);
        if (null !== $registeredTarget) {
            return $registeredTarget;
        }

        $parentEntityClass = $file->getParentEntityClass();
        if (null === $parentEntityClass) {
            return FileValidationTarget::system();
        }

        if (
            Attachment::class === $parentEntityClass
            && self::ATTACHMENT_FILE_FIELD_NAME === $file->getParentEntityFieldName()
        ) {
            $target = $this->getTargetForAttachmentFile($parentEntityClass, $file->getParentEntityId());
            if (null !== $target) {
                return $target;
            }
        }

        return new FileValidationTarget($parentEntityClass, (string)$file->getParentEntityFieldName());
    }

    /**
     * Checks whether a file is submitted together with its attachment, which validates the file itself.
     */
    public function isSubmittedWithAttachment(File $file): bool
    {
        $registeredTarget = $this->targetRegistry->get($file);

        return null !== $registeredTarget
            && Attachment::class === $registeredTarget->entityClass
            && self::ATTACHMENT_FILE_FIELD_NAME === $registeredTarget->fieldName;
    }

    /**
     * Returns null when the attachment has no target entity.
     */
    public function getTargetForAttachment(Attachment $attachment): ?FileValidationTarget
    {
        $target = $attachment->getTarget();
        if (null === $target) {
            return null;
        }

        return new FileValidationTarget($this->doctrineHelper->getClass($target));
    }

    /**
     * Returns null when the attachment cannot be found or has no target entity.
     */
    private function getTargetForAttachmentFile(string $attachmentClass, ?int $attachmentId): ?FileValidationTarget
    {
        if (null === $attachmentId) {
            return null;
        }

        $attachment = $this->doctrineHelper->getEntity($attachmentClass, $attachmentId);
        if (!$attachment instanceof Attachment) {
            return null;
        }

        return $this->getTargetForAttachment($attachment);
    }
}
