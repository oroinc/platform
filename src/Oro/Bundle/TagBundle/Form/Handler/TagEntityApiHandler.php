<?php

namespace Oro\Bundle\TagBundle\Form\Handler;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Persistence\ObjectManager;
use Oro\Bundle\SoapBundle\Form\Handler\ApiFormHandler;
use Oro\Bundle\TagBundle\Entity\TagManager;
use Oro\Bundle\TagBundle\Helper\TaggableHelper;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class TagEntityApiHandler extends ApiFormHandler
{
    /** @var TagManager */
    protected $tagManager;

    /** @var TaggableHelper */
    protected $taggableHelper;

    /**
     * @param FormInterface  $form
     * @param RequestStack   $requestStack
     * @param ObjectManager  $entityManager
     * @param TagManager     $tagManager
     * @param TaggableHelper $helper
     */
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

    /**
     * {@inheritdoc}
     */
    protected function prepareFormData($entity)
    {
        $tags = new ArrayCollection();
        $this->form->setData($tags);

        return ['target' => $entity, 'tags' => $tags];
    }

    /**
     * {@inheritdoc}
     */
    public function process($entity)
    {
        if (!$this->taggableHelper->isTaggable($entity)) {
            throw new \LogicException('Target entity should be taggable.');
        }

        return parent::process($entity);
    }

    /**
     * {@inheritdoc}
     */
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
