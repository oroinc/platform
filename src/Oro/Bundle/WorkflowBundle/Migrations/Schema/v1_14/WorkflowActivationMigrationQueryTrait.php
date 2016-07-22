<?php

namespace Oro\Bundle\WorkflowBundle\Migrations\Schema\v1_14;

use Oro\Bundle\MigrationBundle\Migration\ParametrizedMigrationQuery;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class WorkflowActivationMigrationQuery extends ParametrizedMigrationQuery
{
    /** @var string */
    private static $updateQuery = 'UPDATE oro_workflow_definition SET active=:is_active WHERE name = :workflow_name';

    /** @var string */
    private $workflowName;

    /**
     * @param string $workflowName
     */
    public function __construct($workflowName)
    {
        $this->workflowName = $workflowName;
    }

    /**
     * {@inheritdoc}
     */
    public function execute(LoggerInterface $logger)
    {
        $logger = $logger ?: new NullLogger();

        $this->logQuery(
            $logger,
            self::$updateQuery,
            ['is_active' => true, 'workflow_name' => $this->workflowName],
            ['is_active' => 'boolean', 'workflow_name' => 'string']
        );

        $statement = $this->connection->prepare(self::$updateQuery);
        $statement->bindValue(':is_active', true, 'boolean');
        $statement->bindValue(':workflow_name', $this->workflowName, 'string');

        $statement->execute();
    }

    public function getDescription()
    {
        return sprintf('Updates workflow definition `%s` to be active.', $this->workflowName);
    }
}
