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

    /**
     * @param TagManager     $tagManager
     * @param TaggableHelper $helper
     */
    public function __construct(TagManager $tagManager, TaggableHelper $helper)
    {
        $this->tagManager     = $tagManager;
        $this->taggableHelper = $helper;
    }

    /**
     * {@inheritdoc}
     */
    public function supports($entity, FieldMetadata $metadata)
    {
        return
            $this->taggableHelper->isTaggable($entity) &&
            $metadata->getSourceClassName() === 'Oro\Bundle\TagBundle\Entity\Tag';
    }

    /**
     * {@inheritdoc}
     */
    public function getValue($entity, FieldMetadata $metadata)
    {
        return $this->tagManager->getTags($entity);
    }

    /**
     * {@inheritdoc}
     */
    public function setValue($entity, FieldMetadata $metadata, $value)
    {
        $this->tagManager->setTags($entity, $value);
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'tag';
    }
}
