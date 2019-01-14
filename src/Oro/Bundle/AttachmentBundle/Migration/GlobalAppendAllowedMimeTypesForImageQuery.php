<?php

namespace Oro\Bundle\AttachmentBundle\Migration;

use Oro\Bundle\AttachmentBundle\Tools\MimeTypesConverter;
use Oro\Bundle\MigrationBundle\Migration\ArrayLogger;
use Oro\Bundle\MigrationBundle\Migration\ParametrizedMigrationQuery;
use Psr\Log\LoggerInterface;

/**
 * The migration query that allows to update global image MIME types config with new values.
 */
class GlobalAppendAllowedMimeTypesForImageQuery extends ParametrizedMigrationQuery
{
    private const IMAGE_CONFIG_NAME = 'upload_image_mime_types';
    private const CONFIG_SECTION = 'oro_attachment';

    /**
     * @param array $mimeTypes
     */
    public function __construct(array $mimeTypes)
    {
        $this->mimeTypes = $mimeTypes;
    }

    /**
     * @var array
     */
    private $mimeTypes;

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
     * @param bool $dryRun
     */
    protected function doExecute(LoggerInterface $logger, $dryRun = false)
    {
        foreach ($this->fetchConfigRows($logger) as $row) {
            $id = $row['id'];
            $existingMimeTypes = empty($row['text_value'])
                ? []
                : MimeTypesConverter::convertToArray($row['text_value']);
            // we must store client`s already added custom mime types + our new types without duplicates.
            $updatedMimeTypes = MimeTypesConverter::convertToString(
                array_unique(array_merge($existingMimeTypes, $this->mimeTypes))
            );
            $this->updateConfigValue($id, $updatedMimeTypes, $logger, $dryRun);
        }
    }

    /**
     * @param LoggerInterface $logger
     *
     * @return array
     */
    private function fetchConfigRows(LoggerInterface $logger)
    {
        $sql = 'SELECT c.id, c.text_value FROM oro_config_value AS c WHERE c.name = ? AND c.section = ?';
        $parameters = [self::IMAGE_CONFIG_NAME, self::CONFIG_SECTION];
        $this->logQuery($logger, $sql, $parameters);

        return $this->connection->fetchAll($sql, $parameters);
    }

    /**
     * @param int $id
     * @param string $mimeTypes
     * @param LoggerInterface $logger
     * @param bool $dryRun
     */
    private function updateConfigValue($id, $mimeTypes, LoggerInterface $logger, $dryRun)
    {
        $sql = 'UPDATE oro_config_value SET text_value = :text_value WHERE id = :id';
        $parameters = ['text_value' => $mimeTypes, 'id' => $id];
        $this->logQuery($logger, $sql, $parameters);

        if (!$dryRun) {
            $this->connection->prepare($sql)->execute($parameters);
        }
    }
}
