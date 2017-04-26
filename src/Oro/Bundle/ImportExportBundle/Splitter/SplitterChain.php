<?php

namespace Oro\Bundle\ImportExportBundle\Splitter;

class SplitterChain
{

    /**
     * @var SplitterInterface[]
     */
    private $splitters;

    public function __construct()
    {
        $this->splitters = [];
    }

    /**
     * @param SplitterInterface $splitter
     * @param string $alias
     */
    public function addSplitter(SplitterInterface $splitter, $alias)
    {
        $this->splitters[$alias] = $splitter;
    }

    /**
     * @param $alias
     *
     * @return SplitterInterface|null
     */
    public function getSplitter($alias)
    {
        if (array_key_exists($alias, $this->splitters)) {
            return $this->splitters[$alias];
        }

        return null;
    }
}
