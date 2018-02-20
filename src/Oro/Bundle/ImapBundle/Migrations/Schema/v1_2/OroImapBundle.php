<?php

namespace Oro\Bundle\ImapBundle\Migrations\Schema\v1_2;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroImapBundle implements Migration
{
    /**
     * @inheritdoc
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $queries->addPreQuery("UPDATE oro_email_origin SET imap_ssl='tls' WHERE imap_ssl='tsl';");
    }
}
