<?php

namespace Oro\Bundle\SearchBundle\Datasource;

use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
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

        $this->validateSections($config);

        foreach ((array)$config['from'] as $from) {
            $query->from($from['alias']);
        }

        foreach ($config['select'] as $select) {
            $query->addSelect($select);
        }

        return $query;
    }

    /**
     * @param array $config
     * @return array
     */
    private function validateSections(array $config)
    {
        if (!isset($config['select'])) {
            throw new InvalidConfigurationException('Missing mandatory "select" section');
        }

        if (!isset($config['from'])) {
            throw new InvalidConfigurationException('Missing mandatory "from" section');
        }
    }
}
