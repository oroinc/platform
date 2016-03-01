<?php

namespace Oro\Bundle\TagBundle\Form\EventSubscriber;

use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\TagBundle\Entity\TagManager;
use Oro\Bundle\TagBundle\Helper\TaggableHelper;

/**
 * Loads tagging and assign to entity on pre set
 */
class TagSubscriber implements EventSubscriberInterface
{
    /** @var TagManager */
    protected $tagManager;

    /** @var TaggableHelper */
    protected $taggableHelper;

    /** @var Organization|null */
    protected $organization;

    /**
     * @param TagManager     $tagManager
     * @param TaggableHelper $taggableHelper
     */
    public function __construct(TagManager $tagManager, TaggableHelper $taggableHelper)
    {
        $this->tagManager     = $tagManager;
        $this->taggableHelper = $taggableHelper;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            FormEvents::PRE_SET_DATA => 'preSet'
        ];
    }

    /**
     * @param FormEvent $event
     */
    public function preSet(FormEvent $event)
    {
        $entity = $event->getForm()->getParent()->getData();

        if (!$this->taggableHelper->isTaggable($entity)) {
            return;
        }

        $this->tagManager->loadTagging($entity);
        $tags = $this->tagManager->getPreparedArray($entity, null, $this->organization);

        $event->setData($tags);
    }
}
