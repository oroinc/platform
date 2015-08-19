<?php

namespace Oro\Bundle\EmailBundle\Migrations\Schema\v1_17;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroEmailBundle implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $queries->addQuery('DELETE FROM oro_process_definition WHERE name = "email_auto_response"');
        $queries->addQuery('DELETE FROM oro_process_trigger WHERE definition_name = "email_auto_response"');
    }
}
