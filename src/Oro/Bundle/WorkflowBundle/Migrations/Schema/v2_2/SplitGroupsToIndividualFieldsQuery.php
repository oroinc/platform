<?php

namespace Oro\Bundle\WorkflowBundle\Migrations\Schema\v1_14;

use Doctrine\DBAL\Types\Type;
use Oro\Bundle\MigrationBundle\Migration\ArrayLogger;
use Oro\Bundle\MigrationBundle\Migration\ParametrizedMigrationQuery;
use Psr\Log\LoggerInterface;

class SplitGroupsToIndividualFieldsQuery extends ParametrizedMigrationQuery
{
    const GROUP_TYPE_EXCLUSIVE_ACTIVE = 10;
    const GROUP_TYPE_EXCLUSIVE_RECORD = 20;

    /**
     * {@inheritdoc}
     */
    public function getDescription()
    {
        $logger = new ArrayLogger();
        $this->splitGroups($logger, true);

        return array_merge(
            ['Splits single field "groups" to individual fields:' .
                ' "exclusive_active_groups" and "exclusive_record_groups".'],
            $logger->getMessages()
        );
    }

    /**
     * {@inheritdoc}
     */
    public function execute(LoggerInterface $logger)
    {
        $this->splitGroups($logger);
    }

    /**
     * @param LoggerInterface $logger
     * @param bool $dryRun
     */
    protected function splitGroups(LoggerInterface $logger, $dryRun = false)
    {
        $queries = [];

        // prepare update queries
        $rows = $this->getRows($logger);
        foreach ($rows as $row) {
            $groups = $this->connection->convertToPHPValue($row['groups'], 'array');
            $workflowName = $this->connection->convertToPHPValue($row['name'], 'string');
            $groupDefinitions = [
                self::GROUP_TYPE_EXCLUSIVE_ACTIVE => 'exclusive_active_groups',
                self::GROUP_TYPE_EXCLUSIVE_RECORD => 'exclusive_record_groups',
            ];
            foreach ($groupDefinitions as $groupId => $groupField) {
                $query = sprintf(
                    'UPDATE oro_workflow_definition SET %s = :%s WHERE name = :workflow_name',
                    $groupField,
                    $groupField
                );
                $queries[] = [
                    $query,
                    [
                        $groupField => isset($groups[$groupId]) ? $groups[$groupId] : [],
                        'workflow_name' => $workflowName
                    ],
                    [$groupField => Type::SIMPLE_ARRAY, 'workflow_name' => Type::STRING]
                ];
            }
        }

        // execute update queries
        foreach ($queries as $val) {
            $this->logQuery($logger, $val[0], $val[1], $val[2]);
            if (!$dryRun) {
                $this->connection->executeUpdate($val[0], $val[1], $val[2]);
            }
        }
    }

    /**
     * @param LoggerInterface $logger
     *
     * @return array
     */
    private function getRows(LoggerInterface $logger)
    {
        $query = 'SELECT name, groups FROM oro_workflow_definition';

        $this->logQuery($logger, $query);

        return $this->connection->fetchAll($query);
    }
}
