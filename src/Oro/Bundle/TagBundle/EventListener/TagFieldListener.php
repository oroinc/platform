<?php

namespace Oro\Bundle\TagBundle\EventListener;

use Oro\Bundle\UIBundle\Event\BeforeViewRenderEvent;

use Oro\Bundle\TagBundle\Entity\TagManager;

class TagFieldListener
{
    /** @var TagManager */
    protected $tagManager;

    /**
     * @param TagManager $tagManager
     */
    public function __construct(TagManager $tagManager)
    {
        $this->tagManager = $tagManager;
    }

    /**
     * Add tag field as last field in first data block
     *
     * @param BeforeViewRenderEvent $event
     */
    public function addTagField(BeforeViewRenderEvent $event)
    {
        $entity = $event->getEntity();
        if ($entity && $this->tagManager->isTaggable($entity)) {
            $environment = $event->getTwigEnvironment();
            $data        = $event->getData();

            $tagField = $environment->render(
                'OroTagBundle::tagField.html.twig',
                ['entity' => $entity]
            );

            if (!empty($data['dataBlocks'])) {
                if (isset($data['dataBlocks'][0]['subblocks'])) {
                    array_push($data['dataBlocks'][0]['subblocks'][0]['data'], $tagField);
                }
            }
            $event->setData($data);
        }
    }
}
