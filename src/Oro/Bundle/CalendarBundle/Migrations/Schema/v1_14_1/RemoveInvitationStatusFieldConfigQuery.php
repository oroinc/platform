<?php

namespace Oro\Bundle\CalendarBundle\Migrations\Schema\v1_14_1;

use Psr\Log\LoggerInterface;

use Doctrine\DBAL\Connection;

use Oro\Bundle\MigrationBundle\Migration\ArrayLogger;
use Oro\Bundle\MigrationBundle\Migration\ParametrizedMigrationQuery;

class RemoveInvitationStatusFieldConfigQuery extends ParametrizedMigrationQuery
{
    /**
     * {@inheritdoc}
     */
    public function getDescription()
    {
        $logger = new ArrayLogger();
        $logger->info(
            sprintf(
                'Remove outdated field from entity configuration: %s::$%s.',
                'Oro\Bundle\CalendarBundle\Entity\CalendarEvent',
                'invitationStatus'
            )
        );
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
    public function doExecute(LoggerInterface $logger, $dryRun = false)
    {
        $sql = 'DELETE FROM oro_entity_config_field WHERE field_name = ? '.
            'AND entity_id = (SELECT id FROM oro_entity_config WHERE class_name = ? LIMIT 1)';

        $parameters = ['invitationStatus', 'Oro\Bundle\CalendarBundle\Entity\CalendarEvent'];
        $this->connection->executeUpdate($sql, $parameters);

        $this->logQuery($logger, $sql, $parameters);

        if (!$dryRun) {
            $this->connection->executeUpdate($sql, $parameters);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function setConnection(Connection $connection)
    {
        $this->connection = $connection;
    }
}
