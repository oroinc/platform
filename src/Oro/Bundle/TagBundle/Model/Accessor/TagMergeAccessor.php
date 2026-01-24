<?php

namespace Oro\Bundle\TagBundle\Model\Accessor;

use Oro\Bundle\EntityMergeBundle\Metadata\FieldMetadata;
use Oro\Bundle\EntityMergeBundle\Model\Accessor\AccessorInterface;
use Oro\Bundle\TagBundle\Entity\TagManager;
use Oro\Bundle\TagBundle\Helper\TaggableHelper;

/**
 * Accessor for handling tag operations during entity merge processes.
 *
 * This accessor implements the merge functionality for tags on taggable entities. It provides methods to check
 * if an entity supports tag merging, retrieve tags from a source entity, and set merged tags on a destination entity.
 * It integrates with the entity merge bundle to ensure tags are properly handled when merging entities.
 */
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
