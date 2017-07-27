<?php

namespace Oro\Bundle\TagBundle\EventListener;

use Oro\Bundle\TagBundle\Helper\TaggableHelper;
use Oro\Bundle\UIBundle\Event\BeforeViewRenderEvent;

class TagFieldListener
{
    /** @var TaggableHelper */
    protected $taggableHelper;

    /** @param TaggableHelper $helper */
    public function __construct(TaggableHelper $helper)
    {
        $this->taggableHelper = $helper;
    }

    /**
     * Add tag field as last field in first data block
     *
     * @param BeforeViewRenderEvent $event
     */
    public function addTagField(BeforeViewRenderEvent $event)
    {
        $entity = $event->getEntity();
        if ($entity && $this->taggableHelper->shouldRenderDefault($entity)) {
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
