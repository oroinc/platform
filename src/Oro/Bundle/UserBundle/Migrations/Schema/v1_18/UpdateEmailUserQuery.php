<?php

namespace Oro\Bundle\UserBundle\Migrations\Schema\v1_18;

use Psr\Log\LoggerInterface;

use Oro\Bundle\MigrationBundle\Migration\ArrayLogger;
use Oro\Bundle\MigrationBundle\Migration\ParametrizedMigrationQuery;

/**
 * Depends to the UserBundle
 *
 * Class UpdateEmailUserQuery
 * @package Oro\Bundle\UserBundle\Migrations\Schema\v1_18
 */
class UpdateEmailUserQuery extends ParametrizedMigrationQuery
{
    /**
     * {@inheritDoc}
     */
    public function getDescription()
    {
        $logger = new ArrayLogger();
        $this->updateOrigin($logger, true);
        $this->fillFolders($logger, true);
        $this->updateEmailUserId($logger, true);
        $this->removeDuplicates($logger, true);

        return $logger->getMessages();
    }

    /**
     * {@inheritDoc}
     */
    public function execute(LoggerInterface $logger)
    {
        $this->updateOrigin($logger);
        $this->fillFolders($logger);
        $this->updateEmailUserId($logger);
        $this->removeDuplicates($logger);
    }

    /**
     * @param LoggerInterface $logger
     * @param bool $dryRun
     *
     * @throws \Doctrine\DBAL\DBALException
     */
    protected function updateOrigin(LoggerInterface $logger, $dryRun = false)
    {
        $query  = 'UPDATE oro_email_user AS eu SET origin_id =
            (SELECT ef.origin_id FROM oro_email_folder AS ef WHERE ef.id = eu.folder_id);';
        $params = [];
        $types  = [];
        $this->logQuery($logger, $query, $params, $types);
        if (!$dryRun) {
            $this->connection->executeUpdate($query, $params, $types);
        }
    }

    /**
     * @param LoggerInterface $logger
     * @param bool $dryRun
     *
     * @throws \Doctrine\DBAL\DBALException
     */
    protected function fillFolders(LoggerInterface $logger, $dryRun = false)
    {
        $query  = 'INSERT INTO oro_email_user_folders (email_user_id, folder_id, origin_id, email_id)
            SELECT MIN(eu.id), eu.folder_id, eu.origin_id, eu.email_id
            FROM
                oro_email_user AS eu
            GROUP BY eu.folder_id, eu.origin_id, eu.email_id;';
        $params = [];
        $types  = [];
        $this->logQuery($logger, $query, $params, $types);
        if (!$dryRun) {
            $this->connection->executeUpdate($query, $params, $types);
        }
    }

    /**
     * @param LoggerInterface $logger
     * @param bool $dryRun
     *
     * @throws \Doctrine\DBAL\DBALException
     */
    protected function updateEmailUserId(LoggerInterface $logger, $dryRun = false)
    {
        $query  = 'UPDATE oro_email_user_folders AS euf SET email_user_id =
            (SELECT MIN(eu.id) AS id FROM oro_email_user AS eu
                WHERE eu.origin_id = euf.origin_id AND eu.email_id = euf.email_id);';
        $params = [];
        $types  = [];
        $this->logQuery($logger, $query, $params, $types);
        if (!$dryRun) {
            $this->connection->executeUpdate($query, $params, $types);
        }
    }

    /**
     * @param LoggerInterface $logger
     * @param bool $dryRun
     *
     * @throws \Doctrine\DBAL\DBALException
     */
    protected function removeDuplicates(LoggerInterface $logger, $dryRun = false)
    {
        $query  = 'DELETE FROM oro_email_user WHERE id NOT IN (SELECT *
            FROM (SELECT MIN(eu.id)
                FROM oro_email_user AS eu
                GROUP BY eu.email_id, eu.user_owner_id, eu.organization_id, eu.mailbox_owner_id, eu.origin_id)
                  AS euid);';
        $params = [];
        $types  = [];
        $this->logQuery($logger, $query, $params, $types);
        if (!$dryRun) {
            $this->connection->executeUpdate($query, $params, $types);
        }
    }
}
