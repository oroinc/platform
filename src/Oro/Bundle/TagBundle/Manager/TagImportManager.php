<?php

namespace Oro\Bundle\TagBundle\Manager;

use Oro\Bundle\ImportExportBundle\Converter\RelationCalculatorInterface;
use Oro\Bundle\TagBundle\Entity\Tag;
use Oro\Bundle\TagBundle\Entity\Taggable;
use Oro\Bundle\TagBundle\Entity\TagManager as TagStorage;

class TagImportManager
{
    const TAGS_ORDER = 500;
    const TAGS_FIELD = 'tags';

    /** @var TagStorage */
    protected $tagStorage;

    /** @var RelationCalculatorInterface[] */
    protected $taggableRelatioCalculators;

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
        if (empty($data[static::TAGS_FIELD])) {
            return;
        }

        $tags = array_filter(
            array_map(
                function ($tagsData) {
                    if (!isset($tagsData['name'])) {
                        return null;
                    }

                    return new Tag($tagsData['name']);
                },
                $data[static::TAGS_FIELD]
            )
        );

        return !$tags ? null : [
            'autocomplete' => [],
            'all' => $tags,
            'owner'=> $tags,
        ];
    }

    /**
     * @param array $tags
     *
     * @return array
     */
    public function normalizeTags(array $tags)
    {
        if (empty($tags['all'])) {
            return [];
        }

        return array_map(
            function (Tag $tag) {
                return ['name' => $tag->getName()];
            },
            $tags['all']
        );
    }

    /**
     * @param array $convertDelimiter
     *
     * @return array Where first element is name of the rule and second is the rule itself
     */
    public function createTagRule($convertDelimiter)
    {
        return [
            'Tags (\d+) Name',
            [
                'value' => sprintf('tags%1$s(\d+)%1$sname', $convertDelimiter),
                'order' => static::TAGS_ORDER,
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
        $headers = [];
        $count = $this->getTaggableRelationCalculator($conversionType)
            ->getMaxRelatedEntities($entityName, static::TAGS_FIELD);
        for ($i = 0; $i < $count; $i++) {
            $headers[] = $this->createTagHeader($convertDelimiter, $i);
        }

        return $headers;
    }

    /**
     * @param Taggable $taggable
     */
    public function saveTags(Taggable $taggable)
    {
        $this->tagStorage->saveTagging($taggable);
    }

    /**
     * @param string $conversionType
     * @param RelationCalculatorInterface $relationCalculator
     */
    public function addTaggableRelationCalculator($conversionType, RelationCalculatorInterface $relationCalculator)
    {
        $this->taggableRelatioCalculators[$conversionType] = $relationCalculator;
    }

    /**
     * @param string $convertDelimiter
     * @param int $n
     *
     * @return array
     */
    protected function createTagHeader($convertDelimiter, $n)
    {
        return [
            'value' => sprintf('tags%1$s%2$d%1$sname', $convertDelimiter, $n),
            'order' => static::TAGS_ORDER,
        ];
    }

    /**
     * @param string $conversionType
     *
     * @return RelationCalculatorInterface
     */
    protected function getTaggableRelationCalculator($conversionType)
    {
        return isset($this->taggableRelatioCalculators[$conversionType])
            ? $this->taggableRelatioCalculators[$conversionType]
            : reset($this->taggableRelatioCalculators);
    }
}
