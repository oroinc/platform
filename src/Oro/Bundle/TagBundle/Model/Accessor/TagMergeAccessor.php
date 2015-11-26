<?php

namespace Oro\Bundle\TagBundle\Model\Accessor;

use Oro\Bundle\TagBundle\Entity\TagManager;

use Oro\Bundle\EntityMergeBundle\Metadata\FieldMetadata;
use Oro\Bundle\EntityMergeBundle\Model\Accessor\AccessorInterface;

class TagMergeAccessor implements AccessorInterface
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
     * {@inheritdoc}
     */
    public function supports($entity, FieldMetadata $metadata)
    {
        return
            $this->tagManager->isTaggable($entity) &&
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
