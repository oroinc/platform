<?php

namespace Oro\Bundle\SegmentBundle\Migrations\Schema\v1_12;

use Doctrine\DBAL\Query\QueryBuilder;
use Doctrine\DBAL\Types\Types;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Oro\Bundle\MigrationBundle\Migration\ParametrizedSqlMigrationQuery;
use Psr\Log\LoggerInterface;

/**
 * Updates enum and multiEnum filter values in segment definitions to use the new format with enum code prefix.
 * Before: {"filter":"enum","data":{"value":["tank_pumps"],"params":{"class":"Extend\\Entity\\EV_Product_..."}}}
 * After:  {"filter":"enum","data":{"value":["product_dt_web_level_3_c14c2f41.tank_pumps"],"params":{...}}}
 */
class UpdateEnumFilterValuesQuery extends ParametrizedSqlMigrationQuery
{
    private const int LIMIT = 100;

    #[\Override]
    public function getDescription()
    {
        return 'Fixes enum filters to use new format';
    }

    #[\Override]
    public function execute(LoggerInterface $logger)
    {
        $steps = ceil($this->getSegmentCount() / static::LIMIT);

        $segmentQb = $this->createSegmentQb()
            ->setMaxResults(static::LIMIT);

        for ($i = 0; $i < $steps; $i++) {
            $rows = $segmentQb
                ->setFirstResult($i * static::LIMIT)
                ->executeQuery()
                ->fetchAllAssociative();

            $this->processRows($rows);
            $this->processQueries($logger);
            $this->queries = [];
        }
    }

    private function processRows(array $segments): void
    {
        $segmentsToUpdate = [];

        foreach ($segments as $segment) {
            $definition = $segment['definition'];
            if (!$definition) {
                continue;
            }

            $definition = $this->connection->convertToPHPValue($definition, Types::JSON);
            if (empty($definition['filters'])) {
                continue;
            }

            $needUpdate = false;
            $updatedFilters = $this->processFilters($definition['filters'], $needUpdate);
            if ($needUpdate) {
                $definition['filters'] = $updatedFilters;
                $segmentsToUpdate[$segment['id']] = $definition;
            }
        }

        foreach ($segmentsToUpdate as $id => $definitionToUpdate) {
            $this->addSql(
                'UPDATE oro_segment SET definition = :definition WHERE id = :id',
                [
                    'id' => $id,
                    'definition' => $definitionToUpdate
                ],
                [
                    'id' => Types::INTEGER,
                    'definition' => Types::JSON
                ]
            );
        }
    }

    private function processFilters(array $filtersToProcess, bool &$needUpdate): array
    {
        $updated = [];

        foreach ($filtersToProcess as $filterDefinition) {
            $newDefinition = $filterDefinition;

            if (isset($filterDefinition['criterion'])) {
                if ($this->isEnumFilter($filterDefinition)) {
                    $newDefinition = $this->updateEnumFilterValues($filterDefinition, $needUpdate);
                }
            } elseif (is_array($filterDefinition)) {
                // Process nested filter groups (AND/OR conditions)
                $newDefinition = $this->processFilters($filterDefinition, $needUpdate);
            }

            $updated[] = $newDefinition;
        }

        return $updated;
    }

    private function isEnumFilter(array $filterDefinition): bool
    {
        return isset($filterDefinition['criterion']['filter'])
            && in_array($filterDefinition['criterion']['filter'], ['enum', 'multiEnum'], true)
            && isset($filterDefinition['criterion']['data']['value'])
            && isset($filterDefinition['criterion']['data']['params']['class']);
    }

    private function updateEnumFilterValues(array $filterDefinition, bool &$needUpdate): array
    {
        $values = $filterDefinition['criterion']['data']['value'];

        if (!is_array($values) || empty($values)) {
            return $filterDefinition;
        }

        // Check if values are already in the new format (contain a dot)
        $firstValue = reset($values);
        if (!ExtendHelper::isInternalEnumId($firstValue)) {
            // Already in new format
            return $filterDefinition;
        }

        // Get enum code from the class parameter
        $enumClass = $filterDefinition['criterion']['data']['params']['class'];
        if (!str_starts_with($enumClass, ExtendHelper::ENUM_CLASS_NAME_PREFIX)) {
            // Not a standard enum class, skip
            return $filterDefinition;
        }

        $enumCode = ExtendHelper::getEnumCode($enumClass);
        $newValues = ExtendHelper::mapToEnumOptionIds($enumCode, $values);

        $filterDefinition['criterion']['data']['value'] = $newValues;
        $needUpdate = true;

        return $filterDefinition;
    }

    private function getSegmentCount(): int
    {
        return $this->createSegmentQb()
            ->select('COUNT(1)')
            ->executeQuery()
            ->fetchOne();
    }

    private function createSegmentQb(): QueryBuilder
    {
        return $this->connection->createQueryBuilder()
            ->select('s.id, s.definition')
            ->from('oro_segment', 's');
    }
}
