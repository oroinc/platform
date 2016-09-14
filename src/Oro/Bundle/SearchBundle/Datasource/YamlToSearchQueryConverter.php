<?php

namespace Oro\Bundle\SearchBundle\Datasource;

use Symfony\Component\Config\Definition\Processor;

use Oro\Bundle\SearchBundle\Query\SearchQueryInterface;
use Oro\Bundle\DataGridBundle\Datasource\Orm\QueryConverter\QueryConfiguration;

class YamlToSearchQueryConverter
{
    /**
     * @param SearchQueryInterface $query
     * @param array              $config
     * @return mixed
     */
    public function process(SearchQueryInterface $query, array $config)
    {
        if (!isset($config['query'])) {
            return null;
        }

        $processor = new Processor();
        $config    = $processor->processConfiguration(new QueryConfiguration(), $config);

        foreach ((array)$config['from'] as $from) {
            $query->from($from['alias']);
        }

        foreach ((array)$config['select'] as $select) {
            $query->addSelect($select);
        }

        return $query;
    }
}
