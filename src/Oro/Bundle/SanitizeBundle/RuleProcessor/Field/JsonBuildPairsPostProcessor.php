<?php

namespace Oro\Bundle\SanitizeBundle\RuleProcessor\Field;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\SanitizeBundle\RuleProcessor\Field\Helper\ProcessorHelper;

/**
 * Serialized data fields post-processor that combines all necessary JSON field updates into a single query for a table.
 * Other field sanitizing rule processors fulfill it.
 */
class JsonBuildPairsPostProcessor
{
    private array $jsonBuildPairsMap = [];

    public function __construct(private ManagerRegistry $doctrine, private ProcessorHelper $helper)
    {
    }

    /**
     * The given SQL value must be a valid SQL part with all proper quotations made.
     */
    public function addJsonBuildPairForTable(string $tableName, string $fieldName, string $valueSql)
    {
        if (!isset($this->jsonBuildPairsMap[$tableName])) {
            $this->jsonBuildPairsMap[$tableName] = [];
        }
        $this->jsonBuildPairsMap[$tableName][$fieldName] = $valueSql;

        return $this;
    }

    /**
     * @return string[]
     */
    public function getSqls(): array
    {
        $updateSqls = [];

        foreach ($this->jsonBuildPairsMap as $tableName => $fieldJsonPairMaps) {
            $jsonValueSqlPairs = [];
            ksort($fieldJsonPairMaps);
            foreach ($fieldJsonPairMaps as $fieldName => $valueSql) {
                $jsonValueSqlPairs[] = sprintf("%s, %s", $this->helper->quoteString($fieldName), $valueSql);
            }

            $updateSqls[] = sprintf(
                "UPDATE %s SET serialized_data = serialized_data || jsonb_build_object(%s)",
                $this->helper->quoteIdentifier($tableName),
                implode(', ', $jsonValueSqlPairs)
            );
        }
        $this->jsonBuildPairsMap = [];

        return $updateSqls;
    }
}
