<?php

namespace Oro\Bundle\LocaleBundle\Migrations\Schema\v1_3;

use Doctrine\DBAL\Types\Type;
use Oro\Bundle\MigrationBundle\Migration\ArrayLogger;
use Oro\Bundle\MigrationBundle\Migration\ParametrizedMigrationQuery;
use Oro\Bundle\UserBundle\Entity\User;
use Psr\Log\LoggerInterface;

class CreateRelatedLanguagesQuery extends ParametrizedMigrationQuery
{
    /**
     * {@inheritdoc}
     */
    public function getDescription()
    {
        $logger = new ArrayLogger();
        $logger->info("Creates all required Languages that were used for Localizations");
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
    public function doExecute(LoggerInterface $logger, $dryRun = false)
    {
        list($userId, $organizationId) = $this->getAdminUserAndOrganization($logger);
        $createdAt = date('Y-m-d H:i:s');

        $langsToProcess = array_map(
            function ($item) use ($userId, $organizationId, $createdAt) {
                return [
                    'code' => sprintf("'%s'", $item['language_code']),
                    'organization_id' => sprintf("'%s'", $organizationId),
                    'user_owner_id' => sprintf("'%s'", $userId),
                    'enabled' => sprintf("'%s'", 1),
                    'created_at' => sprintf("'%s'", $createdAt),
                    'updated_at' => sprintf("'%s'", $createdAt),
                ];
            },
            $this->getRelatedLanguages($logger)
        );

        foreach ($langsToProcess as $values) {
            $query = $this->connection->createQueryBuilder()->insert('oro_language')->values($values)->getSQL();
            $this->logQuery($logger, $query);
            if (!$dryRun) {
                $this->connection->executeQuery($query);
            }
        }
    }

    /**
     * @param LoggerInterface $logger
     *
     * @return array
     */
    protected function getRelatedLanguages(LoggerInterface $logger)
    {
        $sql = 'SELECT DISTINCT language_code FROM oro_localization ' .
            'WHERE language_code NOT IN (SELECT code FROM oro_language)';

        $this->logQuery($logger, $sql);

        return $this->connection->fetchAll($sql);
    }

    /**
     * @param LoggerInterface $logger
     *
     * @return array
     */
    protected function getAdminUserAndOrganization(LoggerInterface $logger)
    {
        $sql = $this->connection->createQueryBuilder()
            ->select(['u.id AS user_owner_id', 'u.organization_id'])
            ->from('oro_user', 'u')
            ->innerJoin('u', 'oro_user_access_role', 'rel', 'rel.user_id = u.id')
            ->innerJoin('rel', 'oro_access_role', 'r', 'r.id = rel.role_id')
            ->where('r.role = :role')
            ->setMaxResults(1)
            ->getSQL();
        $params = ['role' => User::ROLE_ADMINISTRATOR];
        $types = ['role' => Type::STRING];

        $this->logQuery($logger, $sql, $params, $types);

        $data = $this->connection->fetchAssoc($sql, $params, $types);

        return [$data['user_owner_id'], $data['organization_id']];
    }
}
