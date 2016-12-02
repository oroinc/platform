<?php

namespace Oro\Bundle\CalendarBundle\Migrations\Schema\v1_14_1;

use Psr\Log\LoggerInterface;

use Doctrine\DBAL\Connection;

use Oro\Bundle\MigrationBundle\Migration\ConnectionAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\MigrationQuery;

class RemoveInvitationStatusFieldConfigQuery implements MigrationQuery, ConnectionAwareInterface
{
    /**
     * @var Connection
     */
    protected $connection;

    /**
     * {@inheritdoc}
     */
    public function getDescription()
    {
        return 'Remove CalendarEvent invitationStatus field configuration';
    }

    /**
     * {@inheritdoc}
     */
    public function execute(LoggerInterface $logger)
    {
        $sql = 'DELETE FROM oro_entity_config_field WHERE field_name=?'.
            'AND entity_id=(SELECT id FROM oro_entity_config WHERE class_name = ? LIMIT 1)';

        $parameters = ['invitationStatus', 'Oro\Bundle\CalendarBundle\Entity\CalendarEvent'];
        $this->connection->executeUpdate($sql, $parameters);
    }

    /**
     * {@inheritdoc}
     */
    public function setConnection(Connection $connection)
    {
        $this->connection = $connection;
    }
}
