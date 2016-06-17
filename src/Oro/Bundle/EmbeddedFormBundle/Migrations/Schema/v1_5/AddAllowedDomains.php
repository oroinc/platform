<?php

namespace Oro\Bundle\EmbeddedFormBundle\Migrations\Schema\v1_5;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class AddAllowedDomains implements Migration
{
    /** @inheritdoc */
    public function up(Schema $schema, QueryBag $queries)
    {
        $table = $schema->getTable('oro_embedded_form');
        $table->addColumn('allowed_domains', 'text', ['notnull' => false]);

        $queries->addPostQuery("UPDATE oro_embedded_form set allowed_domains = '*'");
    }
}
