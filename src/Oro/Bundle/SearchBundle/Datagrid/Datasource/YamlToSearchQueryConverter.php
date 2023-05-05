<?php

namespace Oro\Bundle\SearchBundle\Datagrid\Datasource;

use Oro\Bundle\SearchBundle\Query\AbstractSearchQuery;
use Oro\Bundle\SearchBundle\Query\Criteria\Comparison;
use Oro\Bundle\SearchBundle\Query\Query;
use Oro\Bundle\SearchBundle\Query\SearchQueryInterface;
use Symfony\Component\Config\Definition\Processor;

/**
 * Configure search query by YAML configuration.
 */
class YamlToSearchQueryConverter
{
    /**
     * @param SearchQueryInterface $query
     * @param array $config
     * @return mixed
     */
    public function process(SearchQueryInterface $query, array $config)
    {
        if (empty($config['query'])) {
            return null;
        }
        $hints = $config['hints'] ?? [];

        $processor = new Processor();
        $config = $processor->processConfiguration(new QueryConfiguration(), $this->preProcessConfig($config));

        foreach ((array)$config['from'] as $from) {
            $query->setFrom($from);
        }

        foreach ((array)$config['select'] as $select) {
            $query->addSelect($select);
        }

        $this->processWhere($query, $config);
        $this->processHints($query, $hints);

        return $query;
    }

    /**
     * @param array $config
     * @return array
     */
    protected function preProcessConfig(array $config)
    {
        return [
            'query' => $config['query'],
        ];
    }

    /**
     * @param SearchQueryInterface $query
     * @param array $config
     */
    protected function processWhere(SearchQueryInterface $query, $config)
    {
        if (isset($config['where'])) {
            if (isset($config['where']['and'])) {
                foreach ((array)$config['where']['and'] as $where) {
                    list($field, $operator, $value) = explode(' ', $where, 3);
                    $query->addWhere(new Comparison($field, $operator, $value), AbstractSearchQuery::WHERE_AND);
                }
            }

            if (isset($config['where']['or'])) {
                foreach ((array)$config['where']['or'] as $where) {
                    list($field, $operator, $value) = explode(' ', $where, 3);
                    $query->addWhere(new Comparison($field, $operator, $value), AbstractSearchQuery::WHERE_OR);
                }
            }
        }
    }

    private function processHints(SearchQueryInterface $query, array $hints): void
    {
        if (empty($hints)) {
            return;
        }

        foreach ($hints as $hint) {
            $hintName = $hint;
            $hintValue = true;
            if (\is_array($hint)) {
                $hintName = $hint['name'];
                $hintValue = $hint['value'];
            }

            $query->setHint($this->resolveHintName($hintName), $hintValue);
        }
    }

    private function resolveHintName(string $name): string
    {
        if (\defined(Query::class.'::'.$name)) {
            return \constant(Query::class.'::'.$name);
        }

        return $name;
    }
}
