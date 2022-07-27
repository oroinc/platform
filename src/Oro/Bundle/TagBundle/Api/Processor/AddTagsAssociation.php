<?php

namespace Oro\Bundle\TagBundle\Api\Processor;

use Oro\Bundle\ApiBundle\Processor\GetConfig\ConfigContext;
use Oro\Bundle\ApiBundle\Util\ConfigUtil;
use Oro\Bundle\TagBundle\Entity\Tag;
use Oro\Bundle\TagBundle\Helper\TaggableHelper;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Adds "tags" association to all taggable entities.
 */
class AddTagsAssociation implements ProcessorInterface
{
    private TaggableHelper $taggableHelper;

    public function __construct(TaggableHelper $taggableHelper)
    {
        $this->taggableHelper = $taggableHelper;
    }

    /**
     * {@inheritDoc}
     */
    public function process(ContextInterface $context): void
    {
        /** @var ConfigContext $context */

        if (!$this->taggableHelper->isTaggable($context->getClassName())) {
            return;
        }

        $association = $context->getResult()->getOrAddField('tags');
        $association->setTargetClass(Tag::class);
        $association->setTargetType(ConfigUtil::TO_MANY);
        $association->setPropertyPath(ConfigUtil::IGNORE_PROPERTY_PATH);
    }
}
