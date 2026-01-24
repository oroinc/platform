<?php

namespace Oro\Bundle\TagBundle\Form\Handler;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\SoapBundle\Form\Handler\ApiFormHandler;
use Oro\Bundle\TagBundle\Entity\TagManager;
use Oro\Bundle\TagBundle\Helper\TaggableHelper;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Handles form processing for tag entities in API contexts.
 *
 * This handler extends the base API form handler to provide specialized processing for tags on taggable entities.
 * It manages the transformation of tag data from API requests into tag entities, loads or creates tags as needed,
 * and persists the tag associations to the target entity.
 */
class TagEntityApiHandler extends ApiFormHandler
{
    /** @var TagManager */
    protected $tagManager;

    /** @var TaggableHelper */
    protected $taggableHelper;

    public function __construct(
        FormInterface $form,
        RequestStack $requestStack,
        ObjectManager $entityManager,
        TagManager $tagManager,
        TaggableHelper $helper
    ) {
        parent::__construct($form, $requestStack, $entityManager);

        $this->tagManager     = $tagManager;
        $this->taggableHelper = $helper;
    }

    #[\Override]
    protected function prepareFormData($entity)
    {
        $tags = new ArrayCollection();
        $this->form->setData($tags);

        return ['target' => $entity, 'tags' => $tags];
    }

    #[\Override]
    public function process($entity)
    {
        if (!$this->taggableHelper->isTaggable($entity)) {
            throw new \LogicException('Target entity should be taggable.');
        }

        return parent::process($entity);
    }

    #[\Override]
    protected function onSuccess($entity)
    {
        $targetEntity = $entity['target'];

        /** @var ArrayCollection $tags */
        $tags  = $entity['tags'];
        $names = array_map(
            function ($tag) {
                return $tag['name'];
            },
            $tags->getValues()
        );

        $tags = $this->tagManager->loadOrCreateTags($names);
        $this->tagManager->setTags($targetEntity, new ArrayCollection($tags));
        $this->tagManager->saveTagging($targetEntity);
    }
}
