<?php

namespace Oro\Bundle\TagBundle\Api\Form\Handler;

use Oro\Bundle\ApiBundle\Form\FormUtil;
use Oro\Bundle\TagBundle\Entity\TagManager;
use Symfony\Component\Form\FormInterface;

/**
 * Handles "update", "add" and "delete" operations for forms that have "tags" association of taggable entities.
 */
class TagsAssociationHandler
{
    private TagManager $tagManager;

    public function __construct(TagManager $tagManager)
    {
        $this->tagManager = $tagManager;
    }

    /**
     * Handles "add" operation.
     */
    public function handleAdd(FormInterface $form, string $associationName): void
    {
        $fieldForm = $form->get($associationName);
        if (!FormUtil::isSubmittedAndValid($fieldForm)) {
            return;
        }

        $entity = $form->getData();
        $this->tagManager->addTags($fieldForm->getData(), $entity);
        $this->tagManager->saveTagging($entity, false);
    }

    /**
     * Handles "delete" operation.
     */
    public function handleDelete(FormInterface $form, string $associationName): void
    {
        $fieldForm = $form->get($associationName);
        if (!FormUtil::isSubmittedAndValid($fieldForm)) {
            return;
        }

        $entity = $form->getData();
        $this->tagManager->deleteTags($fieldForm->getData(), $entity);
        $this->tagManager->saveTagging($entity, false);
    }

    /**
     * Handles "update" operation.
     */
    public function handleUpdate(FormInterface $form, string $associationName, bool $flush = false): void
    {
        $fieldForm = $form->get($associationName);
        if (!FormUtil::isSubmittedAndValid($fieldForm)) {
            return;
        }

        $tags = $fieldForm->getData();
        if (null === $tags) {
            return;
        }

        $entity = $form->getData();
        $this->tagManager->setTags($entity, $tags);
        $this->tagManager->saveTagging($entity, $flush);
    }
}
