<?php

namespace Oro\Bundle\TagBundle\Model\Accessor;

use Oro\Bundle\EntityMergeBundle\Metadata\FieldMetadata;
use Oro\Bundle\EntityMergeBundle\Model\Accessor\AccessorInterface;
use Oro\Bundle\TagBundle\Entity\TagManager;
use Oro\Bundle\TagBundle\Helper\TaggableHelper;

class TagMergeAccessor implements AccessorInterface
{
    /** @var TagManager */
    protected $tagManager;

    /** @var TaggableHelper */
    protected $taggableHelper;

    public function __construct(TagManager $tagManager, TaggableHelper $helper)
    {
        $this->tagManager     = $tagManager;
        $this->taggableHelper = $helper;
    }

    #[\Override]
    public function supports($entity, FieldMetadata $metadata)
    {
        return
            $this->taggableHelper->isTaggable($entity) &&
            $metadata->getSourceClassName() === 'Oro\Bundle\TagBundle\Entity\Tag';
    }

    #[\Override]
    public function getValue($entity, FieldMetadata $metadata)
    {
        return $this->tagManager->getTags($entity);
    }

    #[\Override]
    public function setValue($entity, FieldMetadata $metadata, $value)
    {
        $this->tagManager->setTags($entity, $value);
    }

    #[\Override]
    public function getName()
    {
        return 'tag';
    }
}
