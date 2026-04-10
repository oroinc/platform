<?php

namespace Oro\Bundle\AttachmentBundle\Api\Processor;

use Oro\Bundle\ApiBundle\Form\FormUtil;
use Oro\Bundle\ApiBundle\Processor\CustomizeFormData\CustomizeFormDataContext;
use Oro\Bundle\ApiBundle\Request\Constraint;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\PropertyAccess\Exception\ExceptionInterface as PropertyAccessorException;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

/**
 * Links File entity with its parent entity.
 */
class LinkFileToParentEntity implements ProcessorInterface
{
    public function __construct(
        private readonly DoctrineHelper $doctrineHelper,
        private readonly PropertyAccessorInterface $propertyAccessor
    ) {
    }

    #[\Override]
    public function process(ContextInterface $context): void
    {
        /** @var CustomizeFormDataContext $context */

        $form = $context->getForm();
        if (!$form->isValid()) {
            return;
        }

        /** @var File $file */
        $file = $context->getData();

        $parentEntityFieldName = $file->getParentEntityFieldName();
        if (!$parentEntityFieldName) {
            return;
        }

        $parentEntity = $this->findParentEntity($file->getParentEntityClass(), $file->getParentEntityId());
        if (null === $parentEntity) {
            return;
        }

        $previousFile = $this->linkFileToParentEntity($form, $file, $parentEntity, $parentEntityFieldName);
        if (null !== $previousFile) {
            $context->addAdditionalEntityToRemove($previousFile);
        }
    }

    private function findParentEntity(?string $parentEntityClass, ?int $parentEntityId): ?object
    {
        if (null === $parentEntityClass || null === $parentEntityId) {
            return null;
        }

        return $this->doctrineHelper->getEntityManagerForClass($parentEntityClass, false)
            ->find($parentEntityClass, $parentEntityId);
    }

    private function linkFileToParentEntity(
        FormInterface $form,
        File $file,
        object $parentEntity,
        string $parentEntityFieldName
    ): ?File {
        try {
            /** @var File|null $previousFile */
            $previousFile = $this->propertyAccessor->getValue($parentEntity, $parentEntityFieldName);
            if (null === $previousFile) {
                $this->propertyAccessor->setValue($parentEntity, $parentEntityFieldName, $file);
            } elseif ($previousFile->getId() !== $file->getId()) {
                $this->propertyAccessor->setValue($parentEntity, $parentEntityFieldName, $file);
                if ($previousFile->getParentEntityFieldName() !== $parentEntityFieldName) {
                    $previousFile = null;
                }
            } else {
                $previousFile = null;
            }

            return $previousFile;
        } catch (PropertyAccessorException $e) {
            FormUtil::addNamedFormError(
                FormUtil::findFormFieldByPropertyPath($form, 'parentEntityFieldName') ?? $form,
                Constraint::VALUE,
                'Invalid parent entity field name.'
            );

            return null;
        }
    }
}
