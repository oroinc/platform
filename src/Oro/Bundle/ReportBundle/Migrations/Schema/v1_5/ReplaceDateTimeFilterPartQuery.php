<?php

namespace Oro\Bundle\ReportBundle\Migrations\Schema\v1_5;

use Doctrine\DBAL\Types\Type;
use Oro\Bundle\MigrationBundle\Migration\ParametrizedSqlMigrationQuery;
use Psr\Log\LoggerInterface;

class ReplaceDateTimeFilterPartQuery extends ParametrizedSqlMigrationQuery
{
    /**
     * {@inheritdoc}
     */
    protected function processQueries(LoggerInterface $logger, $dryRun = false)
    {
        $reports = $this->connection->createQueryBuilder()
            ->select('r.id, r.definition')
            ->from('oro_report', 'r')
            ->execute()
            ->fetchAll(\PDO::FETCH_ASSOC);
        $reportsToUpdate = [];
        foreach ($reports as $report) {
            $definition = $report['definition'];
            $needUpdate = false;
            if ($definition) {
                $definition = $this->connection->convertToPHPValue($definition, Type::JSON_ARRAY);
                if (!empty($definition['filters'])) {
                    $updated = $this->processFilters($definition['filters'], $needUpdate);
                    if ($needUpdate) {
                        $definition['filters'] = $updated;
                        $reportsToUpdate[$report['id']] = $definition;
                    }
                }
            }
        }

        foreach ($reportsToUpdate as $id => $definitionToUpdate) {
            $this->addSql(
                'UPDATE oro_report SET definition = :definition WHERE id = :id',
                [
                    'id' => $id,
                    'definition' => $definitionToUpdate
                ],
                [
                    'id' => Type::INTEGER,
                    'definition' => Type::JSON_ARRAY
                ]
            );
        }

        parent::processQueries($logger, $dryRun);
    }

    /**
     * @param array $filtersToProcess
     *
     * @param bool  $needUpdate
     *
     * @return array
     */
    protected function processFilters(array $filtersToProcess, &$needUpdate)
    {
        $updated = [];
        foreach ($filtersToProcess as $filterDefinition) {
            $newDefinition = $filterDefinition;
            if (isset($filterDefinition['criterion'])) {
                if (isset($filterDefinition['criterion']['filter']) &&
                    in_array($filterDefinition['criterion']['filter'], ['date', 'datetime'], true) &&
                    isset($filterDefinition['criterion']['data']['part']) &&
                    $filterDefinition['criterion']['data']['part'] === 'source'
                ) {
                    $newDefinition['criterion']['data']['part'] = 'value';
                    $needUpdate = true;
                }
            } elseif (is_array($filterDefinition)) {
                $newDefinition = $this->processFilters($filterDefinition, $needUpdate);
            }

            $updated[] = $newDefinition;
        }

        return $updated;
    }
}
