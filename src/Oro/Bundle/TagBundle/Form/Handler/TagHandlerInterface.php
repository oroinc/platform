<?php

namespace Oro\Bundle\TagBundle\Form\Handler;

use Oro\Bundle\TagBundle\Entity\TagManager;

/**
 * Defines the contract for tag handlers that support dependency injection of the {@see TagManager} service.
 *
 * Implementing classes must provide a {@see TagHandlerInterface::setTagManager()} method to receive
 * the {@see TagManager instance}, enabling them to manage tag operations on taggable entities.
 */
interface TagHandlerInterface
{
    /**
     * Setter for tag manager
     */
    public function setTagManager(TagManager $tagManager);
}
