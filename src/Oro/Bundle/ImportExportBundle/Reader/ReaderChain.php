<?php

namespace Oro\Bundle\ImportExportBundle\Reader;

class ReaderChain
{
    /**
     * @var ReaderInterface[]
     */
    private $readers;

    public function __construct()
    {
        $this->readers = [];
    }

    /**
     * @param ReaderInterface $reader
     * @param string $alias
     */
    public function addReader(ReaderInterface $reader, $alias)
    {
        $this->readers[$alias] = $reader;
    }

    /**
     * @param $alias
     *
     * @return ReaderInterface | null
     */
    public function getReader($alias)
    {
        if (array_key_exists($alias, $this->readers)) {
            return  $this->readers[$alias];
        }

        return null;
    }
}
