<?php

namespace Oro\Bundle\TagBundle\Form\EventSubscriber;

use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\TagBundle\Entity\Taggable;
use Oro\Bundle\TagBundle\Entity\TagManager;
use Oro\Bundle\TagBundle\Form\Transformer\TagTransformer;

/**
 * Class TagSubscriber
 *
 * @package Oro\Bundle\TagBundle\Form\EventSubscriber
 *
 * Loads tagging and assign to entity on pre set
 * Works in way similar to data transformer
 */
class TagSubscriber implements EventSubscriberInterface
{
    /** @var TagManager */
    protected $manager;

    /** @var TagTransformer */
    protected $transformer;

    /** @var Organization|null */
    protected $organization;

    public function __construct(TagManager $manager, TagTransformer $transformer)
    {
        $this->manager     = $manager;
        $this->transformer = $transformer;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            FormEvents::PRE_SET_DATA => 'preSet',
        ];
    }

    /**
     * Loads tagging and transform it to view data
     *
     * @param FormEvent $event
     */
    public function preSet(FormEvent $event)
    {
        $entity = $event->getForm()->getParent()->getData();

        if (!$entity instanceof Taggable) {
            // do nothing if new entity or some error
            return;
        }

        $this->manager->loadTagging($entity);

        $event->setData(['autocomplete' => $entity->getTags()->toArray()]);
    }
}
