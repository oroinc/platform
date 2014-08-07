<?php

namespace Oro\Bundle\UserBundle\Migrations\Schema\v1_5;

use Psr\Log\LoggerInterface;

use Doctrine\DBAL\Types\Type;

use Oro\Bundle\MigrationBundle\Migration\ParametrizedSqlMigrationQuery;
use Oro\Bundle\UserBundle\Entity\User;

class SetOwnerForEmailTemplatesQuery extends ParametrizedSqlMigrationQuery
{
    /**
     * {@inheritdoc}
     */
    protected function processQueries(LoggerInterface $logger, $dryRun = false)
    {
        $this->addSql(
            'UPDATE oro_email_template SET user_owner_id = (' . $this->getAdminUserQuery() . ')',
            ['role' => User::ROLE_ADMINISTRATOR],
            ['role' => Type::STRING]
        );

        parent::processQueries($logger, $dryRun);
    }

    /**
     * @return string
     */
    protected function getAdminUserQuery()
    {
        return $this->connection->createQueryBuilder()
            ->select('u.id')
            ->from('oro_user', 'u')
            ->innerJoin('u', 'oro_user_access_role', 'rel', 'rel.user_id = u.id')
            ->innerJoin('rel', 'oro_access_role', 'r', 'r.id = rel.role_id')
            ->where('r.role = :role')
            ->setMaxResults(1)
            ->getSQL();
    }
}
