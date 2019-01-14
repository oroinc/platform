<?php

namespace Oro\Bundle\AttachmentBundle\Migration;

use Doctrine\DBAL\Types\Type;
use Oro\Bundle\AttachmentBundle\Tools\MimeTypesConverter;
use Oro\Bundle\MigrationBundle\Migration\ArrayLogger;
use Oro\Bundle\MigrationBundle\Migration\ParametrizedMigrationQuery;
use Psr\Log\LoggerInterface;

/**
 * The migration query that allows to update image MIME types config with new values.
 */
class SetAllowedMimeTypesForImageFieldQuery extends ParametrizedMigrationQuery
{
    /**
     * @var string
     */
    private $className;

    /**
     * @var string
     */
    private $fieldName;

    /**
     * @var array
     */
    private $mimeTypes;

    /**
     * @param string $className
     * @param string $fieldName
     * @param array  $mimeTypes
     */
    public function __construct($className, $fieldName, array $mimeTypes)
    {
        $this->className = $className;
        $this->fieldName = $fieldName;
        $this->mimeTypes = $mimeTypes;
    }

    /**
     * {@inheritDoc}
     */
    public function getDescription()
    {
        $logger = new ArrayLogger();
        $this->doExecute($logger, true);

        return $logger->getMessages();
    }

    /**
     * {@inheritDoc}
     */
    public function execute(LoggerInterface $logger)
    {
        $this->doExecute($logger);
    }

    /**
     * @param LoggerInterface $logger
     * @param bool            $dryRun
     */
    protected function doExecute(LoggerInterface $logger, $dryRun = false)
    {
        $row = $this->fetchConfigRow($logger);

        if ($row) {
            $data = $row['data'];
            $id = $row['id'];
            $data = $data ? $this->connection->convertToPHPValue($data, Type::TARRAY) : [];
            if (!isset($data['attachment']['mimetypes'])) {
                $data['attachment']['mimetypes'] = MimeTypesConverter::convertToString($this->mimeTypes);
                $data = $this->connection->convertToDatabaseValue($data, Type::TARRAY);

                $this->updateEntityConfigField($id, $data, $logger, $dryRun);
            }
        }
    }

    /**
     * @param LoggerInterface $logger
     *
     * @return array
     */
    private function fetchConfigRow(LoggerInterface $logger)
    {
        $sql = 'SELECT f.id, f.data
            FROM oro_entity_config_field as f
            INNER JOIN oro_entity_config as e ON f.entity_id = e.id
            WHERE e.class_name = ?
            AND field_name = ?';
        $parameters = [$this->className, $this->fieldName];
        $this->logQuery($logger, $sql);

        return $this->connection->fetchAssoc($sql, $parameters);
    }

    /**
     * @param int             $id
     * @param string[]        $data
     * @param LoggerInterface $logger
     * @param bool            $dryRun
     */
    private function updateEntityConfigField($id, $data, LoggerInterface $logger, $dryRun)
    {
        $sql = 'UPDATE oro_entity_config_field SET data = ? WHERE id = ?';
        $parameters = [$data, $id];
        $this->logQuery($logger, $sql, $parameters);

        if (!$dryRun) {
            $this->connection->prepare($sql)->execute($parameters);
        }
    }
}
