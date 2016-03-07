<?php

namespace Oro\Bundle\TagBundle\Manager;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Util\ClassUtils;

use Oro\Bundle\TagBundle\Entity\Tag;
use Oro\Bundle\TagBundle\Helper\TaggableHelper;
use Oro\Bundle\TagBundle\Entity\TagManager as TagStorage;

class TagImportManager
{
    const TAGS_FIELD = 'tags';

    /** @var TagStorage */
    protected $tagStorage;

    /** @var TaggableHelper */
    protected $taggableHelper;

    /** @var array */
    protected $pendingTags = [];

    /**
     * @param TagStorage $tagStorage
     * @param TaggableHelper $taggableHelper
     */
    public function __construct(TagStorage $tagStorage, TaggableHelper $taggableHelper)
    {
        $this->tagStorage = $tagStorage;
        $this->taggableHelper = $taggableHelper;
    }

    /**
     * @param array $data
     *
     * @return array|null
     */
    public function denormalizeTags(array $data)
    {
        if (empty($data[static::TAGS_FIELD]) || !array_key_exists('name', $data[static::TAGS_FIELD])) {
            return;
        }

        return $this->tagStorage->loadOrCreateTags(array_map(
            'trim',
            explode(
                ',',
                $data[static::TAGS_FIELD]['name']
            )
        ));
    }

    /**
     * @param Collection|null $tags
     *
     * @return array
     */
    public function normalizeTags($tags)
    {
        if (!$tags) {
            return ['name' => ''];
        }

        return [
            'name' => implode(
                ', ',
                array_map(
                    function (Tag $tag) {
                        return $tag->getName();
                    },
                    is_array($tags) ? $tags['all'] : $tags->toArray()
                )
            )
        ];
    }

    /**
     * @param array $convertDelimiter
     *
     * @return array Where first element is name of the rule and second is the rule itself
     */
    public function createTagRule($convertDelimiter)
    {
        return [
            'Tags',
            [
                'value' => sprintf('tags%sname', $convertDelimiter),
                'order' => PHP_INT_MAX,
            ]
        ];
    }

    /**
     * @param string $entityName
     * @param string $convertDelimiter
     * @param string $conversionType
     *
     * @return array
     */
    public function createTagHeaders($entityName, $convertDelimiter, $conversionType)
    {
        return [
            [
                'value' => sprintf('tags%sname', $convertDelimiter),
                'order' => PHP_INT_MAX,
            ]
        ];
    }

    /**
     * @param object $source
     * @param object $dest
     */
    public function moveTags($source, $dest)
    {
        $this->setTags($dest, $this->getTags($source));
        $key = spl_object_hash($source);
        if (isset($this->pendingTags[$key])) {
            unset($this->pendingTags[$key]);
        }
    }

    /**
     * @param object $entity
     */
    public function saveTags($entity)
    {
        $key = spl_object_hash($entity);
        if (isset($this->pendingTags[$key])) {
            $tags = $this->pendingTags[$key] ? new ArrayCollection($this->pendingTags[$key]) : $this->pendingTags[$key];
            $this->tagStorage->setTags($entity, $tags);
            unset($this->pendingTags[$key]);
        }

        $this->tagStorage->saveTagging($entity);
    }

    /**
     * @param object $entity
     * @return Collection|Tag[]
     */
    public function getTags($entity)
    {
        $key = spl_object_hash($entity);
        if (isset($this->pendingTags[$key])) {
            return $this->pendingTags[$key];
        }

        return $this->tagStorage->getTags($entity);
    }

    /**
     * @param object $entity
     * @param Collection|Tag[] $tags
     */
    public function setTags($entity, $tags)
    {
        $this->pendingTags[spl_object_hash($entity)] = $tags;
    }

    /**
     * @param object|string $entity
     *
     * @return bool
     */
    public function isTaggable($entity)
    {
        return $this->taggableHelper->isTaggable($entity);
    }

    /**
     * @param object[] $entities
     */
    public function loadTags(array $entities)
    {
        if (!$entities) {
            return;
        }

        $class = ClassUtils::getClass($entities[0]);
        if (!$this->isTaggable($class)) {
            return;
        }

        $entitiesById = array_combine(
            array_map([$this->taggableHelper, 'getEntityId'], $entities),
            $entities
        );

        $tagsByEntityId = array_reduce(
            $this->tagStorage->getTagsByEntityIds(
                $class,
                array_keys($entitiesById)
            ),
            function (array $tags, array $tag) {
                $tags[$tag['entityId']][] = new Tag($tag['name']);

                return $tags;
            },
            array_fill_keys(array_keys($entitiesById), [])
        );

        array_walk(
            $tagsByEntityId,
            function (array $tags, $entityId) use ($entitiesById) {
                $this->tagStorage->setTags($entitiesById[$entityId], new ArrayCollection($tags));
            }
        );
    }
}
