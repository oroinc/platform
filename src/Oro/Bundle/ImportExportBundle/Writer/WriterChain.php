<?php

namespace Oro\Bundle\ImportExportBundle\Writer;

use Akeneo\Bundle\BatchBundle\Item\ItemWriterInterface;

class WriterChain
{
    /**
     * @var ItemWriterInterface[]
     */
    private $writers;

    public function __construct()
    {
        $this->writers = [];
    }

    /**
     * @param ItemWriterInterface $writer
     * @param $alias
     */
    public function addWriter(ItemWriterInterface $writer, $alias)
    {
        $this->writers[$alias] = $writer;
    }

    /**
     * @param $alias
     *
     * @return ItemWriterInterface | null
     */
    public function getWriter($alias)
    {
        if (array_key_exists($alias, $this->writers)) {
            return $this->writers[$alias];
        }

        return null;
    }
}
