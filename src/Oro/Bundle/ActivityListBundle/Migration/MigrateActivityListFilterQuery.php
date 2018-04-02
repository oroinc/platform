<?php

namespace Oro\Bundle\ActivityListBundle\Migration;

use Doctrine\DBAL\Types\Type;
use Oro\Bundle\MigrationBundle\Migration\ParametrizedSqlMigrationQuery;
use Psr\Log\LoggerInterface;

class MigrateActivityListFilterQuery extends ParametrizedSqlMigrationQuery
{
    /** @var string */
    protected $tableName;

    /**
     * @param string $tableName
     */
    public function __construct($tableName)
    {
        $this->tableName = $tableName;
        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function processQueries(LoggerInterface $logger, $dryRun = false)
    {
        $reports = $this->connection->createQueryBuilder()
            ->select('r.id, r.definition')
            ->from($this->tableName, 'r')
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
                sprintf('UPDATE %s SET definition = :definition WHERE id = :id', $this->tableName),
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
            if (isset($filterDefinition['criteria']) && $filterDefinition['criteria'] === 'condition-activity') {
                $newDefinition['columnName'] = '';
                $newDefinition['criterion']['data']['activityFieldName']
                    = base64_decode($filterDefinition['columnName']);
                $needUpdate = true;
            }
            $updated[] = $newDefinition;
        }
        return $updated;
    }
}
