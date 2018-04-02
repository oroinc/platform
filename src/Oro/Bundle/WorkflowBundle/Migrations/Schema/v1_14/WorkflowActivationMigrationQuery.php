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

    /** @var boolean */
    private $isActive;

    /**
     * @param string $workflowName
     * @param boolean $isActive
     */
    public function __construct($workflowName, $isActive)
    {
        $this->workflowName = $workflowName;
        $this->isActive = $isActive;
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
            ['is_active' => $this->isActive, 'workflow_name' => $this->workflowName],
            ['is_active' => 'boolean', 'workflow_name' => 'string']
        );

        $statement = $this->connection->prepare(self::$updateQuery);
        $statement->bindValue(':is_active', $this->isActive, 'boolean');
        $statement->bindValue(':workflow_name', $this->workflowName, 'string');

        $statement->execute();
    }

    /**
     * {@inheritdoc}
     */
    public function getDescription()
    {
        return sprintf(
            'Update workflow definition `%s` to be %sactive.',
            $this->workflowName,
            $this->isActive ? '' : 'in'
        );
    }
}
