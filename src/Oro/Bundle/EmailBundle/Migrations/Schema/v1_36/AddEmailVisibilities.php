<?php

namespace Oro\Bundle\EmailBundle\Migrations\Schema\v1_36;

use Doctrine\DBAL\Platforms\PostgreSqlPlatform;
use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Extension\DatabasePlatformAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\Extension\DatabasePlatformAwareTrait;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Oro\Bundle\MigrationBundle\Migration\SqlMigrationQuery;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class AddEmailVisibilities implements Migration, DatabasePlatformAwareInterface, ContainerAwareInterface
{
    use DatabasePlatformAwareTrait;

    private ContainerInterface $container;

    /**
     * {@inheritDoc}
     */
    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    /**
     * {@inheritDoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $visibilityTableCreated = $this->addEmailAddressVisibilityTable($schema);
        $this->addIsPrivateColumnToEmailUserTable($schema);

        $this->createCaseInsensitiveIndexForMailboxEmail($queries);

        if ($visibilityTableCreated) {
            $queries->addQuery(new UpdateVisibilitiesMigrationQuery(
                $this->container->get('oro_message_queue.message_producer')
            ));
        }
    }

    private function addEmailAddressVisibilityTable(Schema $schema): bool
    {
        if ($schema->hasTable('oro_email_address_visibility')) {
            return false;
        }

        $table = $schema->createTable('oro_email_address_visibility');
        $table->addColumn('email', 'string', ['length' => 255]);
        $table->addColumn('organization_id', 'integer', ['notnull' => true]);
        $table->addColumn('is_visible', 'boolean', []);
        $table->setPrimaryKey(['email', 'organization_id']);
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_organization'),
            ['organization_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );

        return true;
    }

    private function addIsPrivateColumnToEmailUserTable(Schema $schema): void
    {
        $table = $schema->getTable('oro_email_user');
        if ($table->hasColumn('is_private')) {
            return;
        }

        $table->addColumn('is_private', 'boolean', ['notnull' => false]);
    }

    private function createCaseInsensitiveIndexForMailboxEmail(QueryBag $queries): void
    {
        if ($this->platform instanceof PostgreSqlPlatform) {
            $queries->addPostQuery(new SqlMigrationQuery(
                'CREATE INDEX IF NOT EXISTS idx_mailbox_email_ci ON oro_email_mailbox (LOWER(email))'
            ));
        }
    }
}
