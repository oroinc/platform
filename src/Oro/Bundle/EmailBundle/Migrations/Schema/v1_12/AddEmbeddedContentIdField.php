<?php

namespace Oro\Bundle\EmailBundle\Migrations\Schema\v1_8;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class AddEmbeddedContentIdField implements Migration
{
    /**
     * {@inheritDoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        self::addEmbeddedContentIdField($schema);
    }

    /**
     * @param Schema $schema
     *
     * @throws \Doctrine\DBAL\Schema\SchemaException
     */
    public static function addEmbeddedContentIdField(Schema $schema)
    {
        $table = $schema->getTable('oro_email_attachment');
        $table->addColumn('embedded_content_id', 'string', ['length' => 255]);
    }
}
