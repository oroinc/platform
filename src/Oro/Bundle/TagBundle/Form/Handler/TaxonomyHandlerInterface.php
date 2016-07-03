<?php

namespace Oro\Bundle\TagBundle\Form\Handler;

use Oro\Bundle\TagBundle\Entity\TagManager;

interface TaxonomyHandlerInterface
{
    /**
     * Setter for tag manager
     *
     * @param TagManager $tagManager
     */
    public function setTagManager(TagManager $tagManager);
}
