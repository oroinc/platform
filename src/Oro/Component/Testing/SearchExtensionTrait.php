<?php
namespace Oro\Component\Testing;

use Oro\Bundle\SearchBundle\Engine\IndexerInterface;

trait SearchExtensionTrait
{
    /**
     * @return IndexerInterface
     */
    protected function getSearchIndexer()
    {
        return $this->getContainer()->get('oro_search.search.engine.indexer');
    }
}
