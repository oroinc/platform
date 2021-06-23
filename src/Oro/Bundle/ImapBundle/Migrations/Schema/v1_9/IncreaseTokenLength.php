<?php

namespace Oro\Bundle\ImapBundle\Migrations\Schema\v1_9;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

/**
 * Increased the size of fields as there are no restrictions on length of tokens from external services.
 */
class IncreaseTokenLength implements Migration
{
    /**
     * @param Schema $schema
     * @param QueryBag $queries
     */
    public function up(Schema $schema, QueryBag $queries): void
    {
        $this->changeEmailAccessTokenLength($schema);
        $this->changeEmailRefreshTokenLength($schema);
    }

    /**
     * @param Schema $schema
     */
    private function changeEmailAccessTokenLength(Schema $schema): void
    {
        $table = $schema->getTable('oro_email_origin');
        $table->changeColumn('access_token', ['length' => 8192]);
    }

    /**
     * @param Schema $schema
     */
    private function changeEmailRefreshTokenLength(Schema $schema): void
    {
        $table = $schema->getTable('oro_email_origin');
        $table->changeColumn('refresh_token', ['length' => 8192]);
    }
}
