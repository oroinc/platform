<?php

namespace Oro\Bundle\TagBundle\Form\Handler;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Persistence\ObjectManager;

use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;

use Oro\Bundle\TagBundle\Entity\Tag;
use Oro\Bundle\TagBundle\Entity\TagManager;
use Oro\Bundle\SoapBundle\Form\Handler\ApiFormHandler;

class TagEntityApiHandler extends ApiFormHandler
{
    /** @var TagManager */
    protected $tagManager;

    /**
     * @param FormInterface $form
     * @param Request       $request
     * @param ObjectManager $entityManager
     * @param TagManager    $tagManager
     */
    public function __construct(
        FormInterface $form,
        Request $request,
        ObjectManager $entityManager,
        TagManager $tagManager
    ) {
        parent::__construct($form, $request, $entityManager);
        $this->tagManager = $tagManager;
    }

    /**
     * {@inheritdoc}
     */
    protected function prepareFormData($entity)
    {
        $targets = new ArrayCollection();
        $this->form->setData($targets);

        return ['tag' => $entity, 'targets' => $targets];
    }

    /**
     * {@inheritdoc}
     */
    protected function onSuccess($entity)
    {
        /** @var Tag $tag */
        $tag = $entity['tag'];
        /** @var ArrayCollection $targets */
        $targets = $entity['targets'];

        foreach ($targets as $target) {
            if (!$this->tagManager->isTaggable($target)) {
                // @todo: Change exception/message.
                throw new \InvalidArgumentException('Entity should be taggable');
            }
            $this->tagManager->loadTagging($target);
            $this->tagManager->addTag($tag, $target);
            $this->tagManager->saveTagging($target);
        }
    }
}
