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

    /** @var ArrayCollection[] */
    protected $pendingTags = [];

    /**
     * New imported tags which are not yet persisted are stored here to prevent creation tags with the same names
     *
     * @var array @var array ['tag_name' => Tag]
     */
    protected $loadedTags = [];
    
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
     * Converts tags from strings ("tag1, tag2") into arrays (["tag1", "tag2"])
     * and returns them in form of Tag[] objects
     *
     * @param array $data
     *
     * @return array|null
     */
    public function denormalizeTags(array $data)
    {
        $tags = $tagsToLoad = [];
        if (isset($data[static::TAGS_FIELD]['name'])) {
            $tagNames = explode(',', $data[static::TAGS_FIELD]['name']);
            foreach ($tagNames as $tagName) {
                $tagName = trim($tagName);
                if (isset($this->loadedTags[$tagName])) {
                    $tags[] = $this->loadedTags[$tagName];
                } else {
                    $tagsToLoad[] = $tagName;
                }
            }
            if (!empty($tagsToLoad)) {
                $loadedTags = $this->tagStorage->loadOrCreateTags($tagsToLoad);
                foreach ($loadedTags as $loadedTag) {
                    $this->loadedTags[$loadedTag->getName()] = $loadedTag;
                    $tags[] = $loadedTag;
                }
            }

        }

        return $tags;
    }

    public function clear()
    {
        $this->loadedTags = [];
    }

    /**
     * Converts $tags collection into string representation
     * ["name" => "tag1, tag2"]
     *
     * @param Collection|null $tags
     *
     * @return array
     */
    public function normalizeTags(Collection $tags = null)
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
                    $tags->toArray()
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
    public function persistTags($entity)
    {
        $key = spl_object_hash($entity);
        if (isset($this->pendingTags[$key])) {
            $this->tagStorage->setTags($entity, $this->pendingTags[$key]);
            unset($this->pendingTags[$key]);
        }

        $this->tagStorage->saveTagging($entity, false);
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
        $this->pendingTags[spl_object_hash($entity)] = is_array($tags) ? new ArrayCollection($tags) : $tags;
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

        $class = ClassUtils::getClass(reset($entities));
        if (!$this->isTaggable($class)) {
            return;
        }

        /*
         * map of entities by their ids
         * [int => object, ...]
         * where int is id of the entity and object is the entity itself
         */
        $entitiesById = array_combine(
            array_map([$this->taggableHelper, 'getEntityId'], $entities),
            $entities
        );

        /*
         * map of array of tags by related entity ids
         * [int => Tag[], ...]
         * where int is id of the related entity and Tag[] is list of the entity tags
         *
         * Tags here are loaded in 1 query for all given entities.
         * In case entity have no tags, the array of tags is empty
         */
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

        /*
         * Loads tags into corresponding entities
         */
        array_walk(
            $tagsByEntityId,
            function (array $tags, $entityId) use ($entitiesById) {
                $this->tagStorage->setTags($entitiesById[$entityId], new ArrayCollection($tags));
            }
        );
    }
}
