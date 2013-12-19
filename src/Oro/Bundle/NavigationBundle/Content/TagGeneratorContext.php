<?php

namespace Oro\Bundle\NavigationBundle\Content;

class TagGeneratorContext
{
    /** @var array|TagGeneratorInterface[] */
    protected $generators = [];

    public function __construct($generators)
    {
        $this->generators = $generators;
    }

    /**
     * Delegate tag generation to registered strategies
     * Strategy should be responsive for cache calls for the same data
     *
     * @param mixed $data
     * @param bool  $includeCollectionTag
     *
     * @return array
     */
    public function generate($data, $includeCollectionTag = false)
    {
        $tags = [];

        foreach ($this->generators as $generator) {
            if ($generator->supports($data)) {
                $tags = array_merge($generator->generate($data, $includeCollectionTag));
            }
        }
        $tags = array_unique($tags);

        return $tags;
    }
}
