<?php

namespace Oro\Bundle\SegmentBundle\Migrations\Schema\v1_4;

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
        $segments = $this->connection->createQueryBuilder()
            ->select('s.id, s.definition')
            ->from('oro_segment', 's')
            ->execute()
            ->fetchAll(\PDO::FETCH_ASSOC);
        $segmentsToUpdate = [];
        foreach ($segments as $segment) {
            $definition = $segment['definition'];
            $needUpdate = false;
            if ($definition) {
                $definition = $this->connection->convertToPHPValue($definition, Type::JSON_ARRAY);
                if (!empty($definition['filters'])) {
                    $updated = $this->processFilters($definition['filters'], $needUpdate);
                    if ($needUpdate) {
                        $definition['filters'] = $updated;
                        $segmentsToUpdate[$segment['id']] = $definition;
                    }
                }
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
