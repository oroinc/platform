<?php

namespace Oro\Bundle\TagBundle\Manager;

use Doctrine\Common\Collections\Collection;

use Oro\Bundle\TagBundle\Entity\Tag;
use Oro\Bundle\TagBundle\Entity\Taggable;
use Oro\Bundle\TagBundle\Entity\TagManager as TagStorage;

class TagImportManager
{
    const TAGS_FIELD = 'tags';

    /** @var TagStorage */
    protected $tagStorage;

    /**
     * @param TagStorage $tagStorage
     */
    public function __construct(TagStorage $tagStorage)
    {
        $this->tagStorage = $tagStorage;
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

        $tags = array_map(
            function ($tag) {
                return new Tag($tag);
            },
            array_map(
                'trim',
                explode(
                    ',',
                    $data[static::TAGS_FIELD]['name']
                )
            )
        );

        return !$tags ? null : [
            'autocomplete' => [],
            'all' => $tags,
            'owner'=> $tags,
        ];
    }

    /**
     * @param array|Collection|null $tags
     *
     * @return array
     */
    public function normalizeTags($tags)
    {
        if (!$tags || (is_array($tags) && empty($tags['all']))) {
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
     * @param Taggable $taggable
     */
    public function saveTags(Taggable $taggable)
    {
        $this->tagStorage->saveTagging($taggable);
    }

    /**
     * @param Taggable $taggable
     */
    public function loadTags(Taggable $taggable)
    {
        $this->tagStorage->loadTagging($taggable);
    }
}
