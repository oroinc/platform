<?php

namespace Oro\Bundle\TagBundle\Provider;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityBundle\Provider\VirtualFieldProviderInterface;
use Oro\Bundle\TagBundle\Helper\TaggableHelper;

/**
 * Provides virtual tag field support for taggable entities.
 *
 * This provider enables the tag field to be treated as a virtual field in queries and
 * entity configurations, allowing tags to be accessed and queried as if they were a
 * regular entity field. It integrates with the entity bundle's virtual field system
 * to provide seamless tag field access across the application.
 */
class TagVirtualFieldProvider implements VirtualFieldProviderInterface
{
    public const TAG_FIELD = 'tag_field';

    /** @var TaggableHelper */
    protected $taggableHelper;

    /** @var DoctrineHelper */
    protected $doctrineHelper;

    public function __construct(TaggableHelper $taggableHelper, DoctrineHelper $doctrineHelper)
    {
        $this->taggableHelper = $taggableHelper;
        $this->doctrineHelper = $doctrineHelper;
    }

    #[\Override]
    public function isVirtualField($className, $fieldName)
    {
        return
            $fieldName === self::TAG_FIELD
            && $this->taggableHelper->isTaggable($className);
    }

    #[\Override]
    public function getVirtualFieldQuery($className, $fieldName)
    {
        return [
            'select' => [
                'expr'        => 'entity.' . $this->doctrineHelper->getSingleEntityIdentifierFieldName($className),
                'label'       => 'oro.tag.entity_plural_label',
                'return_type' => 'tag',
                'related_entity_name' => 'Oro\Bundle\TagBundle\Entity\Tag',
            ]
        ];
    }

    #[\Override]
    public function getVirtualFields($className)
    {
        if ($this->taggableHelper->isTaggable($className)) {
            return [self::TAG_FIELD];
        }

        return [];
    }
}
