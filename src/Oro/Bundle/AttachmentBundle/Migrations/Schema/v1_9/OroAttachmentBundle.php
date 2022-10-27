<?php

namespace Oro\Bundle\AttachmentBundle\Migrations\Schema\v1_9;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\FetchMode;
use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\EntityConfigBundle\Migration\UpdateEntityConfigFieldValueQuery;
use Oro\Bundle\MigrationBundle\Migration\ConnectionAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroAttachmentBundle implements Migration, ConnectionAwareInterface
{
    /** @var Connection */
    private $connection;

    /**
     * {@inheritdoc}
     */
    public function setConnection(Connection $connection): void
    {
        $this->connection = $connection;
    }

    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries): void
    {
        $sql = 'select field_name, class_name'
            . ' from oro_entity_config_field oecf'
            . ' inner join oro_entity_config oec on oecf.entity_id = oec.id'
            . ' where oecf.type IN (?, ?)';

        $fileFieldsQueryResult = $this->connection->executeQuery($sql, ['file', 'image']);
        while ($row = $fileFieldsQueryResult->fetch(FetchMode::ASSOCIATIVE)) {
            $queries->addQuery(
                new UpdateEntityConfigFieldValueQuery(
                    $row['class_name'],
                    $row['field_name'],
                    'importexport',
                    'process_as_scalar',
                    false
                )
            );
        }

        $fileFieldsQueryResult = $this->connection->executeQuery($sql, ['multiFile', 'multiImage']);
        while ($row = $fileFieldsQueryResult->fetch(FetchMode::ASSOCIATIVE)) {
            $queries->addQuery(
                new UpdateEntityConfigFieldValueQuery(
                    $row['class_name'],
                    $row['field_name'],
                    'importexport',
                    'full',
                    true
                )
            );
        }

        $queries->addQuery(
            new UpdateEntityConfigFieldValueQuery(
                'Oro\Bundle\AttachmentBundle\Entity\File',
                'uuid',
                'importexport',
                'identity',
                true
            )
        );

        $queries->addQuery(
            new UpdateEntityConfigFieldValueQuery(
                'Oro\Bundle\AttachmentBundle\Entity\FileItem',
                'id',
                'importexport',
                'excluded',
                true
            )
        );
    }
}
