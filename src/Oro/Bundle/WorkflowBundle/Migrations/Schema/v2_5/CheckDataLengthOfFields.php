<?php

namespace Oro\Bundle\WorkflowBundle\Migrations\Schema\v2_5;

use Doctrine\DBAL\Connection;
use Oro\Bundle\MigrationBundle\Migration\ConnectionAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\MigrationQuery;
use Psr\Log\LoggerInterface;

class CheckDataLengthOfFields implements MigrationQuery, ConnectionAwareInterface
{
    /** @var Connection */
    private $connection;

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
    public function getDescription()
    {
        return [
            'Checks data length for the following fields:',
            '  - oro_process_trigger.field: max length should be less than or equal to 150',
            '  - oro_workflow_trans_trigger.field: max length should be less than or equal to 150',
            '  - oro_workflow_restriction.field: max length should be less than or equal to 150',
            '  - oro_workflow_restriction.mode: max length should be less than or equal to 8'
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function execute(LoggerInterface $logger)
    {
        $conflicts = '';
        $conflicts .= $this->getConflicts($logger, 'oro_process_trigger', 'field', 150);
        $conflicts .= $this->getConflicts($logger, 'oro_workflow_trans_trigger', 'field', 150);
        $conflicts .= $this->getConflicts($logger, 'oro_workflow_restriction', 'field', 150);
        $conflicts .= $this->getConflicts($logger, 'oro_workflow_restriction', 'mode', 8);

        if ($conflicts) {
            throw new \LogicException("Found the following conflicts that must be fixed manually:\n" . $conflicts);
        }
    }

    /**
     * @param LoggerInterface $logger
     * @param string          $tableName
     * @param string          $fieldName
     * @param int             $maxLength
     *
     * @return string
     */
    private function getConflicts(LoggerInterface $logger, $tableName, $fieldName, $maxLength)
    {
        $query = sprintf(
            'SELECT id, %1$s, %3$s AS fieldLen FROM %2$s WHERE %1$s IS NOT NULL AND %3$s > %4$d ORDER BY id',
            $fieldName,
            $tableName,
            $this->connection->getDatabasePlatform()->getLengthExpression($fieldName),
            $maxLength
        );
        $logger->info($query);
        $stmt = $this->connection->executeQuery($query);

        $rows = $stmt->fetchAll(\PDO::FETCH_NUM);

        $result = '';
        foreach ($rows as list($id, $value, $length)) {
            $result .= sprintf(
                "Table: %s. Row ID: %s. Field: '%s'. Field Value: '%s'."
                . " Expected Max Length: %d. Actual Length: %d.\n",
                $tableName,
                $id,
                $fieldName,
                $value,
                $maxLength,
                $length
            );
        }

        return $result;
    }
}
