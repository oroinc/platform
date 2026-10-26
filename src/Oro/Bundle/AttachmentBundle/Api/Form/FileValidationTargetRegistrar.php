<?php

declare(strict_types=1);

namespace Oro\Bundle\AttachmentBundle\Api\Form;

use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\AttachmentBundle\Model\FileValidationTarget;
use Oro\Bundle\AttachmentBundle\Provider\FileValidationTargetRegistry;
use Symfony\Component\Form\Exception\TransformationFailedException;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Security\Acl\Util\ClassUtils;

/**
 * Registers the entity and field a new file is submitted to, so the file can be validated against the restrictions
 * of that field before the parent entity is flushed.
 */
class FileValidationTargetRegistrar
{
    /** @var \WeakMap<File, \WeakReference<object>> */
    private \WeakMap $parentEntities;

    public function __construct(
        private readonly FileValidationTargetRegistry $targetRegistry
    ) {
        $this->parentEntities = new \WeakMap();
    }

    public function register(File $file, FormInterface $form): void
    {
        if (null !== $file->getId()) {
            return;
        }

        $parentEntity = $form->getParent()?->getData();
        $fieldName = (string)$form->getPropertyPath();
        if (!\is_object($parentEntity) || '' === $fieldName) {
            return;
        }

        $target = new FileValidationTarget(ClassUtils::getRealClass($parentEntity), $fieldName);

        $this->assertNotSubmittedToAnotherTarget($file, $target, $parentEntity);
        $this->assertSubmittedParentMatches($file, $target, $parentEntity);

        $this->targetRegistry->register($file, $target);
        $this->parentEntities[$file] = \WeakReference::create($parentEntity);
    }

    public function clear(): void
    {
        $this->parentEntities = new \WeakMap();
    }

    /**
     * @throws TransformationFailedException
     */
    private function assertNotSubmittedToAnotherTarget(
        File $file,
        FileValidationTarget $target,
        object $parentEntity
    ): void {
        $registeredTarget = $this->targetRegistry->get($file);
        if (null === $registeredTarget) {
            return;
        }

        $registeredParentEntity = ($this->parentEntities[$file] ?? null)?->get();
        $isSameParent = null === $registeredParentEntity || $registeredParentEntity === $parentEntity;
        if (!$isSameParent || $registeredTarget->getKey() !== $target->getKey()) {
            throw new TransformationFailedException(
                'A new file cannot be used in several fields.',
                0,
                null,
                'oro.attachment.parent.new_file_single_field'
            );
        }
    }

    /**
     * @throws TransformationFailedException
     */
    private function assertSubmittedParentMatches(File $file, FileValidationTarget $target, object $parentEntity): void
    {
        $submittedParentEntityClass = $file->getParentEntityClass();
        if (null === $submittedParentEntityClass) {
            return;
        }

        $submittedTarget = new FileValidationTarget(
            $submittedParentEntityClass,
            (string)$file->getParentEntityFieldName()
        );
        $isSameTarget = $submittedTarget->getKey() === $target->getKey();
        if (!$isSameTarget || !$this->isSubmittedParentIdMatches($file, $parentEntity)) {
            throw new TransformationFailedException(
                'The parent of a new file does not match the field the file is added to.',
                0,
                null,
                'oro.attachment.parent.new_file_parent_mismatch'
            );
        }
    }

    private function isSubmittedParentIdMatches(File $file, object $parentEntity): bool
    {
        $submittedParentEntityId = $file->getParentEntityId();
        if (null === $submittedParentEntityId) {
            return true;
        }

        $parentEntityId = method_exists($parentEntity, 'getId') ? $parentEntity->getId() : null;

        return $submittedParentEntityId === $parentEntityId;
    }
}
