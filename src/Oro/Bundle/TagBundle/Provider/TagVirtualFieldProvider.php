<?php

namespace Oro\Bundle\TagBundle\Provider;

use Doctrine\ORM\Query\Expr\Join;

use Oro\Bundle\TagBundle\Helper\TaggableHelper;
use Oro\Bundle\EntityBundle\EntityConfig\GroupingScope;
use Oro\Bundle\EntityBundle\Provider\VirtualFieldProviderInterface;

class TagVirtualFieldProvider implements VirtualFieldProviderInterface
{
    const TAG_FIELD = 'tag_field';

    /** @var TaggableHelper */
    protected $taggableHelper;

    /** @param TaggableHelper $helper */
    public function __construct(TaggableHelper $helper)
    {
        $this->taggableHelper = $helper;
    }

    /**
     * {@inheritdoc}
     */
    public function isVirtualField($className, $fieldName)
    {
        return
            $this->taggableHelper->isTaggable($className) &&
            $fieldName === self::TAG_FIELD;
    }

    /**
     * {@inheritdoc}
     */
    public function getVirtualFieldQuery($className, $fieldName)
    {
        return [
            'select' => [
                'expr'                => 'entity.name',
                'label'               => 'oro.tag.entity_plural_label',
                'return_type'         => 'tag',
                'related_entity_name' => 'Oro\Bundle\TagBundle\Entity\Tag',
            ]
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getVirtualFields($className)
    {
        if ($this->taggableHelper->isTaggable($className)) {
            return [self::TAG_FIELD];
        }

        return [];
    }
}
