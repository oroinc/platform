<?php

namespace Oro\Bundle\ImapBundle\Migrations\Schema\v1_5;

use Doctrine\DBAL\Platforms\PostgreSqlPlatform;
use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\MigrationBundle\Migration\Extension\DatabasePlatformAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\Extension\DatabasePlatformAwareTrait;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroImapBundle implements Migration, DatabasePlatformAwareInterface
{
    use DatabasePlatformAwareTrait;

    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        if ($this->platform instanceof PostgreSqlPlatform) {
            $queries->addQuery('ALTER TABLE oro_email_origin ALTER imap_password TYPE TEXT');
        } else {
            $queries->addQuery('ALTER TABLE oro_email_origin CHANGE imap_password imap_password LONGTEXT DEFAULT NULL');
        }
    }
}
