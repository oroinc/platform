<?php

namespace Oro\Bundle\TagBundle\EventListener;

use Oro\Bundle\TagBundle\Helper\TaggableHelper;
use Oro\Bundle\UIBundle\Event\BeforeViewRenderEvent;

/**
 * Adds tag field information.
 */
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
     */
    public function addTagField(BeforeViewRenderEvent $event)
    {
        $entity = $event->getEntity();
        if ($entity && $this->taggableHelper->shouldRenderDefault($entity)) {
            $environment = $event->getTwigEnvironment();
            $data        = $event->getData();

            $tagField = $environment->render(
                '@OroTag/tagField.html.twig',
                ['entity' => $entity]
            );

            if (!empty($data['dataBlocks'])) {
                $firstBlockIndex = array_key_first($data['dataBlocks']);

                if (isset($data['dataBlocks'][$firstBlockIndex]['subblocks'])) {
                    array_push($data['dataBlocks'][$firstBlockIndex]['subblocks'][0]['data'], $tagField);
                }
            }
            $event->setData($data);
        }
    }
}
