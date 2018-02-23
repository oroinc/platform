<?php

namespace Oro\Bundle\TagBundle\Provider;

use Doctrine\ORM\Query\Expr\Join;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityBundle\Provider\VirtualRelationProviderInterface;
use Oro\Bundle\TagBundle\Helper\TaggableHelper;

class TagVirtualRelationProvider implements VirtualRelationProviderInterface
{
    const RELATION_NAME = 'tags_virtual';
    const TARGET_ALIAS  = 'virtualTag';

    /** @var TaggableHelper */
    protected $taggableHelper;

    /** @var DoctrineHelper */
    protected $doctrineHelper;

    /**
     * @param TaggableHelper $taggableHelper
     * @param DoctrineHelper $doctrineHelper
     */
    public function __construct(TaggableHelper $taggableHelper, DoctrineHelper $doctrineHelper)
    {
        $this->taggableHelper = $taggableHelper;
        $this->doctrineHelper = $doctrineHelper;
    }

    /**
     * {@inheritdoc}
     */
    public function isVirtualRelation($className, $fieldName)
    {
        return
            $fieldName === self::RELATION_NAME
            && $this->taggableHelper->isTaggable($className);
    }

    /**
     * {@inheritdoc}
     */
    public function getVirtualRelationQuery($className, $fieldName)
    {
        $relations = $this->getVirtualRelations($className);

        return isset($relations[$fieldName])
            ? $relations[$fieldName]['query']
            : [];
    }

    /**
     * {@inheritdoc}
     */
    public function getVirtualRelations($className)
    {
        return $this->taggableHelper->isTaggable($className)
            ? [self::RELATION_NAME => $this->getRelationDefinition($className)]
            : [];
    }

    /**
     * {@inheritdoc}
     */
    public function getTargetJoinAlias($className, $fieldName, $selectFieldName = null)
    {
        return self::TARGET_ALIAS;
    }

    /**
     * @param string $className
     *
     * @return array
     */
    protected function getRelationDefinition($className)
    {
        return [
            'label'               => 'oro.tag.entity_plural_label',
            'relation_type'       => 'ManyToMany',
            'related_entity_name' => 'Oro\Bundle\TagBundle\Entity\Tag',
            'target_join_alias'   => self::TARGET_ALIAS,
            'query'               => [
                'join' => [
                    'left' => [
                        [
                            'join'          => 'Oro\Bundle\TagBundle\Entity\Tagging',
                            'alias'         => 'virtualTagging',
                            'conditionType' => Join::WITH,
                            'condition'     => sprintf(
                                '(virtualTagging.entityName = \'%s\' and virtualTagging.recordId = entity.%s)',
                                $className,
                                $this->doctrineHelper->getSingleEntityIdentifierFieldName($className)
                            )
                        ],
                        [
                            'join'  => 'virtualTagging.tag',
                            'alias' => self::TARGET_ALIAS
                        ]
                    ]
                ]
            ]
        ];
    }
}
