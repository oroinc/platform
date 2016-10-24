<?php

namespace Oro\Bundle\SyncBundle\Content;

class TagGeneratorChain implements TagGeneratorInterface
{
    /** @var TagGeneratorInterface[] */
    protected $generators = [];

    /**
     * @param array $generators
     */
    public function __construct(array $generators = [])
    {
        array_walk(
            $generators,
            function ($generator) {
                if (!$generator instanceof TagGeneratorInterface) {
                    throw new \LogicException('Generator should implement "TagGeneratorInterface"');
                }
            }
        );
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
     * Delegate tag generation to registered strategies
     * Strategy should be responsible for cache calls for the same data
     *
     * {@inheritdoc}
     */
    public function generate($data, $includeCollectionTag = false, $processNestedData = false)
    {
        $tags = [];

        foreach ($this->generators as $generator) {
            if ($generator->supports($data)) {
                $tags = array_merge($tags, $generator->generate($data, $includeCollectionTag, $processNestedData));
            }
        }
        $tags = array_unique($tags);

        return $tags;
    }
}
