<?php

namespace Oro\Bundle\SyncBundle\Content;

/**
 * Delegates the generation of tags to child generators.
 * Child generators should be responsible for cache calls for the same data.
 */
class ChainTagGenerator implements TagGeneratorInterface
{
    /** @var iterable|TagGeneratorInterface[] */
    private $generators;

    /**
     * @param iterable|TagGeneratorInterface[] $generators
     */
    public function __construct(iterable $generators)
    {
        $this->generators = $generators;
    }

    /**
     * {@inheritdoc}
     */
    public function supports($data)
    {
        foreach ($this->generators as $generator) {
            if ($generator->supports($data)) {
                return true;
            }
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function generate($data, $includeCollectionTag = false, $processNestedData = false)
    {
        $tags = [];
        foreach ($this->generators as $generator) {
            if ($generator->supports($data)) {
                $tags[] = $generator->generate($data, $includeCollectionTag, $processNestedData);
            }
        }
        if ($tags) {
            $tags = array_values(array_unique(array_merge(...$tags)));
        }

        return $tags;
    }
}
