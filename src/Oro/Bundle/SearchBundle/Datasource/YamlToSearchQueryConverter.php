<?php

namespace Oro\Bundle\SearchBundle\Datasource;

use Symfony\Component\Config\Definition\Processor;

use Oro\Bundle\SearchBundle\Query\SearchQueryInterface;

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
        $config    = $processor->processConfiguration(new QueryConfiguration(), $this->preProcessConfig($config));

        foreach ((array)$config['from'] as $from) {
            $query->from($from);
        }

        foreach ((array)$config['select'] as $select) {
            $query->addSelect($select);
        }

        return $query;
    }

    /**
     * @param array $config
     * @return array
     */
    protected function preProcessConfig(array $config) {
        unset($config['type']);
        return $config;
    }
}
