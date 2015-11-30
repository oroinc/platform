<?php

namespace Oro\Bundle\TagBundle\Provider;

use Doctrine\ORM\Query\Expr\Join;

use Oro\Bundle\EntityBundle\Provider\VirtualRelationProviderInterface;
use Oro\Bundle\TagBundle\Helper\TaggableHelper;

class TagVirtualRelationProvider implements VirtualRelationProviderInterface
{
    const RELATION_NAME = 'tags_virtual';
    const TARGET_ALIAS  = 'virtualTag';

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
    public function isVirtualRelation($className, $fieldName)
    {
        return $this->isTaggable($className) && $fieldName === self::RELATION_NAME;
    }

    /**
     * {@inheritdoc}
     */
    public function getVirtualRelationQuery($className, $fieldName)
    {
        $relations = $this->getVirtualRelations($className);
        if ($fieldName && array_key_exists($fieldName, $relations)) {
            return $relations[$fieldName]['query'];
        }

        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function getVirtualRelations($className)
    {
        if ($this->isTaggable($className)) {
            return [self::RELATION_NAME => $this->getRelationDefinition($className)];
        }

        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function getTargetJoinAlias($className, $fieldName, $selectFieldName = null)
    {
        return self::TARGET_ALIAS;
    }

    /**
     * Return true if given class name is taggable
     *
     * @param $className
     *
     * @return bool
     */
    protected function isTaggable($className)
    {
        return $this->taggableHelper->isTaggable($className);
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
                                "(virtualTagging.entityName = '%s' and virtualTagging.recordId = entity.id)",
                                $className
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
