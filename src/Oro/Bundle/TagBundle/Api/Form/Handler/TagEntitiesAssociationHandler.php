<?php

namespace Oro\Bundle\TagBundle\Api\Form\Handler;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\ApiBundle\Form\FormUtil;
use Oro\Bundle\TagBundle\Entity\TagManager;
use Symfony\Component\Form\FormInterface;

/**
 * Handles "update", "add" and "delete" operations for forms that have "entities" association of Tag entity.
 */
class TagEntitiesAssociationHandler
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

        $tag = $form->getData();
        $entities = $fieldForm->getData();
        foreach ($entities as $entity) {
            $this->tagManager->addTag($tag, $entity);
            $this->tagManager->saveTagging($entity, false);
        }
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

        $tag = $form->getData();
        $entities = $fieldForm->getData();
        foreach ($entities as $entity) {
            $this->tagManager->deleteTag($tag, $entity);
            $this->tagManager->saveTagging($entity, false);
        }
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

        $entities = $fieldForm->getData();
        if (null === $entities) {
            return;
        }

        $tag = $form->getData();
        $oldEntities = new ArrayCollection($this->tagManager->getEntities($tag));
        foreach ($entities as $entity) {
            if (!$oldEntities->contains($entity)) {
                $oldEntities->add($entity);
                $this->tagManager->addTag($tag, $entity);
                $this->tagManager->saveTagging($entity, $flush);
            }
        }
        foreach ($oldEntities as $oldEntity) {
            if (!$entities->contains($oldEntity)) {
                $this->tagManager->deleteTag($tag, $oldEntity);
                $this->tagManager->saveTagging($oldEntity, $flush);
            }
        }
    }
}
