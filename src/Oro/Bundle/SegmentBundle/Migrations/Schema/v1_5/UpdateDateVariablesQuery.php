<?php

namespace Oro\Bundle\SegmentBundle\Migrations\Schema\v1_5;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\QueryBuilder;

use Psr\Log\LoggerInterface;

use Oro\Bundle\MigrationBundle\Migration\ConnectionAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\MigrationQuery;

class UpdateDateVariablesQuery implements MigrationQuery, ConnectionAwareInterface
{
    const LIMIT = 100;

    /** @var Connection */
    protected $connection;

    /** @var string */
    protected $segmentTable;

    /**
     * @param string $segmentTable
     */
    public function __construct($segmentTable)
    {
        $this->segmentTable = $segmentTable;
    }

    /**
     * {@inheritdoc}
     */
    public function getDescription()
    {
        return 'Fixes month variables to use new format';
    }

    /**
     * {@inheritdoc}
     */
    public function setConnection(Connection $connection)
    {
        $this->connection = $connection;
    }

    /**
     * {@inheritdoc}
     */
    public function execute(LoggerInterface $logger)
    {
        $steps = ceil($this->getSegmentCount() / static::LIMIT);

        $segmentQb = $this->createSegmentQb()
            ->setMaxResults(static::LIMIT);

        for ($i = 0; $i < $steps; $i++) {
            $rows = $segmentQb
                ->setFirstResult($i * static::LIMIT)
                ->execute()
                ->fetchAll(\PDO::FETCH_ASSOC);

            foreach ($rows as $row) {
                $this->processRow($row);
            }
        }
    }

    /**
     * @param array $row
     */
    private function processRow(array $row)
    {
        $definition = $this->connection->convertToPHPValue($row['definition'], Type::JSON_ARRAY);
        if (empty($definition['filters'])) {
            return;
        }

        $updatedFilters = $this->processFilters($definition['filters']);
        if (!$updatedFilters) {
            return;
        }
        $definition['filters'] = $updatedFilters;

        $this->connection->update(
            $this->segmentTable,
            ['definition' => $definition],
            ['id' => $row['id']],
            [Type::JSON_ARRAY]
        );
    }

    /**
     * @param array $filters
     *
     * @return array|false
     */
    private function processFilters(array $filters)
    {
        $updates = [];
        foreach ($filters as $key => $filter) {
            if (isset($filter['criterion'])) {
                $updates[$key] = $this->updateFilterValue($filter);
            } elseif (is_array($filter)) {
                $updates[$key] = $this->processFilters($filter);
            }
        }

        $validUpddates = array_filter($updates);
        if (!$validUpddates) {
            return false;
        }

        $updatedFilters = $validUpddates + $filters;
        ksort($updatedFilters);

        return $updatedFilters;
    }

    /**
     * @param array $filter
     *
     * @return array|false
     */
    private function updateFilterValue(array $filter)
    {
        if (!isset(
            $filter['criterion']['filter'],
            $filter['criterion']['data'],
            $filter['criterion']['data']['part']
        ) ||
            !in_array($filter['criterion']['filter'], ['date', 'datetime']) ||
            $filter['criterion']['data']['part'] !== 'month'
        ) {
            return false;
        }

        $value = $filter['criterion']['data']['value'];
        if ($newStart = $this->updateMonthValue($value, 'start')) {
            $filter['criterion']['data']['value']['start'] = $newStart;
        }
        if ($newEnd = $this->updateMonthValue($value, 'end')) {
            $filter['criterion']['data']['value']['end'] = $newEnd;
        }

        return !$newStart && !$newEnd ? false : $filter;
    }

    /**
     * Replaces old month variables ({{17}} - {{28}}) by month numbers (1 - 12)
     *
     * @param string $value
     * @param string $offset
     *
     * @return string|false Replaced value or false if nothing was replaced
     */
    private function updateMonthValue(array $value, $offset)
    {
        if (!isset($value[$offset])) {
            return false;
        }

        $matches = [];
        if (preg_match('/\{\{(1[7-9]|2[0-8])\}\}/', $value[$offset], $matches)) {
            return (string) ($matches[1] - 16);
        }

        return false;
    }

    /**
     * @return int
     */
    private function getSegmentCount()
    {
        return $this->createSegmentQb()
            ->select('COUNT(1)')
            ->execute()
            ->fetchColumn();
    }

    /**
     * @return QueryBuilder
     */
    private function createSegmentQb()
    {
        return $this->connection->createQueryBuilder()
            ->select('s.id AS id, s.definition as definition')
            ->from($this->segmentTable, 's');
    }
}
