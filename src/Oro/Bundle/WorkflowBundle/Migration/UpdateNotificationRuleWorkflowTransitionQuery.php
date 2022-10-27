<?php

namespace Oro\Bundle\WorkflowBundle\Migration;

use Doctrine\DBAL\Types\Types;
use Oro\Bundle\MigrationBundle\Migration\ArrayLogger;
use Oro\Bundle\MigrationBundle\Migration\ParametrizedMigrationQuery;
use Psr\Log\LoggerInterface;

/**
 * Change workflow transition name for notification rule on workflow transition event.
 */
class UpdateNotificationRuleWorkflowTransitionQuery extends ParametrizedMigrationQuery
{
    /**
     * @var string
     */
    private $entityName;

    /**
     * @var string
     */
    private $workflowName;

    /**
     * @var string
     */
    private $templateName;

    /**
     * @var string
     */
    private $oldTransitionName;

    /**
     * @var string
     */
    private $newTransitionName;

    public function __construct(
        string $entityName,
        string $workflowName,
        string $templateName,
        string $oldTransitionName,
        string $newTransitionName
    ) {
        $this->entityName = $entityName;
        $this->workflowName = $workflowName;
        $this->templateName = $templateName;
        $this->oldTransitionName = $oldTransitionName;
        $this->newTransitionName = $newTransitionName;
    }

    /**
     * {@inheritdoc}
     */
    public function getDescription()
    {
        $logger = new ArrayLogger();
        $logger->info('Updates workflow transition name for notification rules.');

        $this->doExecute($logger, true);

        return $logger->getMessages();
    }

    /**
     * {@inheritdoc}
     */
    public function execute(LoggerInterface $logger)
    {
        $this->doExecute($logger);
    }

    /**
     * {@inheritdoc}
     */
    private function doExecute(LoggerInterface $logger, $dryRun = false): void
    {
        $query = 'UPDATE oro_notification_email_notif 
                  SET workflow_transition_name = :newTransitionName 
                  WHERE entity_name = :entityName and 
                  workflow_definition_name = :workflowDefinitionName and
                  workflow_transition_name = :transitionName and 
                  template_id = :templateId';
        $types = [
            'templateId' => Types::INTEGER,
            'newTransitionName' => Types::STRING,
            'entityName' => Types::STRING,
            'workflowDefinitionName' => Types::STRING,
            'transitionName' => Types::STRING,
        ];

        $params = [
            'templateId' => $this->getTemplateId(),
            'newTransitionName' => $this->newTransitionName,
            'entityName' => $this->entityName,
            'workflowDefinitionName' => $this->workflowName,
            'transitionName' => $this->oldTransitionName
        ];

        $this->logQuery($logger, $query, $params, $types);
        if (!$dryRun) {
            $this->connection->executeStatement($query, $params, $types);
        }
    }

    /**
     * Get quote_created template id
     *
     * @throws \Doctrine\DBAL\DBALException
     */
    private function getTemplateId(): int
    {
        return $this->connection->fetchColumn(
            'SELECT id FROM oro_email_template WHERE name = :name',
            [
                'name' => $this->templateName
            ]
        );
    }
}
