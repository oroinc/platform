<?php

namespace Oro\Bundle\IntegrationBundle\Writer;

use Akeneo\Bundle\BatchBundle\Item\ItemWriterInterface;

class ReverseWriter implements ItemWriterInterface
{
    /**
     * {@inheritDoc}
     */
    public function write(array $items)
    {
        foreach ($items as $item) {
            $transport = $this->getTransport($item);
        }
    }

    /**
     * @param Object $item
     *
     * @return Object
     */
    protected function getTransport($item)
    {
        return $item->getChannel()->getTransport();
    }
}
