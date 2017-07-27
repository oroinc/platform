<?php

namespace Oro\Bundle\NavigationBundle\Title\TitleReader;

class TitleReaderRegistry
{
    /** @var ReaderInterface[] */
    private $readers = [];

    /**
     * @param ReaderInterface $reader
     */
    public function addTitleReader(ReaderInterface $reader)
    {
        $this->readers[] = $reader;
    }

    /**
     * @return ReaderInterface[]
     */
    public function getTitleReaders()
    {
        return $this->readers;
    }

    /**
     * @param string $route
     *
     * @return string|null
     */
    public function getTitleByRoute($route)
    {
        foreach ($this->getTitleReaders() as $titleReader) {
            $title = $titleReader->getTitle($route);
            if ($title !== null) {
                return $title;
            }
        }

        return null;
    }
}
