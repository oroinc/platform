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
     * {@inheritDoc}
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
     * {@inheritdoc}
     */
    public function doExecute(LoggerInterface $logger, $dryRun = false)
    {
        $result = $this->connection->fetchAll(
            $this->getAdminUserAndOrganizationQuery($logger),
            ['role' => User::ROLE_ADMINISTRATOR],
            ['role' => Type::STRING]
        );

        $result = reset($result);
        $userId = $result['id'];
        $organizationId = $result['organization_id'];
        $createdAt = date('Y-m-d H:i:s');

        $langsToProcess = array_map(
            function ($item) use ($userId, $organizationId, $createdAt) {
                return [
                    'code' => sprintf("'%s'", $item['language_code']),
                    'organization_id' => sprintf("'%s'", $organizationId),
                    'user_owner_id' => sprintf("'%s'", $userId),
                    'enabled' => sprintf("'%s'", 1),
                    'created_at' => sprintf("'%s'", $createdAt),
                ];
            },
            $this->connection->fetchAll($this->getRelatedLanguagesQuery($logger))
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
     * @return string
     */
    protected function getRelatedLanguagesQuery(LoggerInterface $logger)
    {
        $qb = $this->connection->createQueryBuilder();

        $query = $qb->select(['distinct lz.language_code'])
            ->from('oro_localization', 'lz')
            ->where($qb->expr()->notIn('lz.language_code', $this->getInstalledLanguagesQuery($logger)))
            ->getSQL();

        $this->logQuery($logger, $query);

        return $query;
    }

    /**
     * @param LoggerInterface $logger
     *
     * @return string
     */
    protected function getInstalledLanguagesQuery(LoggerInterface $logger)
    {
        $query = $this->connection->createQueryBuilder()
            ->select(['l.code'])
            ->from('oro_language', 'l')
            ->getSQL();
        $this->logQuery($logger, $query);

        return $query;
    }

    /**
     * @param LoggerInterface $logger
     *
     * @return string
     */
    protected function getAdminUserAndOrganizationQuery(LoggerInterface $logger)
    {
        $query = $this->connection->createQueryBuilder()
            ->select(['u.id', 'u.organization_id'])
            ->from('oro_user', 'u')
            ->innerJoin('u', 'oro_user_access_role', 'rel', 'rel.user_id = u.id')
            ->innerJoin('rel', 'oro_access_role', 'r', 'r.id = rel.role_id')
            ->where('r.role = :role')
            ->setMaxResults(1)
            ->getSQL();
        $this->logQuery($logger, $query);

        return $query;
    }
}
