<?php

namespace Oro\Bundle\UserBundle\Migrations\Schema\v2_8;

use Doctrine\DBAL\Types\Types;
use Oro\Bundle\MigrationBundle\Migration\ParametrizedMigrationQuery;
use Oro\Bundle\SecurityBundle\Tools\UUIDGenerator;
use Psr\Log\LoggerInterface;

class MigrateUserLoginAttemptsQuery extends ParametrizedMigrationQuery
{
    private const READ_BATCH_SIZE = 1000;
    private const INSERT_BATCH_SIZE = 100;

    /**
     * {@inheritDoc}
     */
    public function getDescription()
    {
        return 'Migrate data from "oro_logger_log_entry" to "oro_user_login".';
    }

    /**
     * {@inheritDoc}
     */
    public function execute(LoggerInterface $logger)
    {
        $this->doExecute($logger);
    }

    private function doExecute(LoggerInterface $logger, bool $dryRun = false): void
    {
        $this->insertLoginAttempts($logger, $dryRun);
        $this->deleteLogRecords($logger, $dryRun);
    }

    private function loadLogRecords(LoggerInterface $logger): iterable
    {
        $sql = $this->connection->getDatabasePlatform()->modifyLimitQuery(
            'SELECT id, datetime, message, context FROM oro_logger_log_entry WHERE channel = ? AND id > ? ORDER BY id',
            self::READ_BATCH_SIZE
        );

        $lastId = 0;
        do {
            $params = ['oro_account_security', $lastId];
            $types = [Types::STRING, Types::INTEGER];
            $this->logQuery($logger, $sql, $params, $types);
            $rows = $this->connection->fetchAllAssociative($sql, $params, $types);
            foreach ($rows as $row) {
                $lastId = $row['id'];
                $row['datetime'] = $this->connection->convertToPHPValue($row['datetime'], Types::DATETIME_MUTABLE);
                $row['context'] = $this->connection->convertToPHPValue($row['context'], Types::JSON_ARRAY);
                yield $row;
            }
        } while ($rows);
    }

    private function insertLoginAttempts(LoggerInterface $logger, bool $dryRun): void
    {
        $sqlTemplate = 'INSERT INTO oro_user_login'
            . ' (id, attempt_at, success, source, username, user_id, ip, context)'
            . ' VALUES ';

        $userIdMap = [];
        $i = 0;
        $sqlValues = '';
        $params = [];
        $types = [];
        $rows = $this->loadLogRecords($logger);
        foreach ($rows as $row) {
            $i++;

            $context = $row['context'];
            $username = $context['user']['username'] ?? $context['username'];
            $userId = $this->getUserId($logger, $userIdMap, $context['user']['id'] ?? null);
            $ipAddress = $context['ipaddress'] ?? null;
            unset(
                $context['user']['username'],
                $context['username'],
                $context['user']['id'],
                $context['ipaddress']
            );

            if ($sqlValues) {
                $sqlValues .= ',';
            }
            $sqlValues .= '(?, ?, ?, ?, ?, ?, ?, ?)';
            $params[] = UUIDGenerator::v4();
            $params[] = $row['datetime'];
            $params[] = 'Successful login' === $row['message'];
            $params[] = 1;
            $params[] = $username;
            $params[] = $userId;
            $params[] = $ipAddress;
            $params[] = $context;
            $types[] = Types::STRING;
            $types[] = Types::DATETIME_MUTABLE;
            $types[] = Types::BOOLEAN;
            $types[] = Types::INTEGER;
            $types[] = Types::STRING;
            $types[] = Types::INTEGER;
            $types[] = Types::STRING;
            $types[] = Types::JSON;
            if ($i < self::INSERT_BATCH_SIZE) {
                continue;
            }

            $sql = $sqlTemplate . $sqlValues;
            $this->logQuery($logger, $sql, $params, $types);
            if (!$dryRun) {
                $this->connection->executeStatement($sql, $params, $types);
                $i = 0;
                $sqlValues = '';
                $params = [];
                $types = [];
            }
        }
    }

    private function getUserId(LoggerInterface $logger, array &$userIdMap, ?int $userId): ?int
    {
        if (null === $userId) {
            return null;
        }

        if (!isset($userIdMap[$userId])) {
            $sql = 'SELECT id FROM oro_user WHERE id = ?';
            $params = [$userId];
            $types = [Types::INTEGER];
            $this->logQuery($logger, $sql, $params, $types);
            $userIdMap[$userId] = (false !== $this->connection->fetchOne($sql, $params, $types));
        }

        return $userIdMap[$userId] ? $userId : null;
    }

    private function deleteLogRecords(LoggerInterface $logger, bool $dryRun): void
    {
        $sql = 'DELETE FROM oro_logger_log_entry WHERE channel = ?';
        $params = ['oro_account_security'];
        $types = [Types::STRING];

        $this->logQuery($logger, $sql, $params, $types);
        if (!$dryRun) {
            $this->connection->executeStatement($sql, $params, $types);
        }
    }
}
