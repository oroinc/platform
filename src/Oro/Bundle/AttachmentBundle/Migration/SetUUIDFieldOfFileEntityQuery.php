<?php

namespace Oro\Bundle\AttachmentBundle\Migration;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Type;
use Oro\Bundle\MigrationBundle\Migration\ArrayLogger;
use Oro\Bundle\MigrationBundle\Migration\ParametrizedMigrationQuery;
use Psr\Log\LoggerInterface;

/**
 * The migration query that allows to fill the empty UUID field of the File entity.
 */
class SetUUIDFieldOfFileEntityQuery extends ParametrizedMigrationQuery
{
    private const BATCH_SIZE = 1000;

    /** @var AbstractPlatform */
    private $databasePlatform;

    public function __construct(AbstractPlatform $databasePlatform)
    {
        $this->databasePlatform = $databasePlatform;
    }

    /**
     * {@inheritdoc}
     */
    public function getDescription(): array
    {
        $logger = new ArrayLogger();
        $this->doExecute($logger, true);

        return $logger->getMessages();
    }

    /**
     * {@inheritdoc}
     */
    public function execute(LoggerInterface $logger): void
    {
        $this->doExecute($logger);
    }

    /**
     * @param LoggerInterface $logger
     * @param bool $dryRun
     */
    private function doExecute(LoggerInterface $logger, $dryRun = false): void
    {
        $query = 'SELECT MIN(id) AS start, MAX(id) AS end FROM oro_attachment_file WHERE uuid IS NULL LIMIT 1';

        $this->logQuery($logger, $query);

        $data = $this->connection->fetchAssoc($query);

        $this->executeQuery($logger, $data['start'] ?? 0, $data['end'] ?? 0, $dryRun);
    }

    /**
     * @param LoggerInterface $logger
     * @param int $start
     * @param int $end
     * @param bool $dryRun
     */
    private function executeQuery(LoggerInterface $logger, int $start, int $end, $dryRun): void
    {
        $query = sprintf(
            'UPDATE oro_attachment_file SET uuid = %s WHERE uuid IS NULL AND id BETWEEN :start AND :end',
            $this->databasePlatform->getGuidExpression()
        );
        $types = ['start' => Type::INTEGER, 'end' => Type::INTEGER];

        while ($start <= $end) {
            $params = ['start' => $start, 'end' => $start + self::BATCH_SIZE];

            $this->logQuery($logger, $query, $params, $types);
            if (!$dryRun) {
                $this->connection->executeStatement($query, $params, $types);
            }

            $start += self::BATCH_SIZE;
        }
    }
}
