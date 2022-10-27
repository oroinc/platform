<?php

namespace Oro\Bundle\NavigationBundle\Title\TitleReader;

/**
 * Contains all page title readers and use them to get a page title for a specific route.
 */
class TitleReaderRegistry
{
    /** @var iterable|ReaderInterface[] */
    private $readers;

    /**
     * @param iterable|ReaderInterface[] $readers
     */
    public function __construct(iterable $readers)
    {
        $this->readers = $readers;
    }

    public function getTitleByRoute(string $route): ?string
    {
        foreach ($this->readers as $reader) {
            $title = $reader->getTitle($route);
            if (null !== $title) {
                return $title;
            }
        }

        return null;
    }
}
