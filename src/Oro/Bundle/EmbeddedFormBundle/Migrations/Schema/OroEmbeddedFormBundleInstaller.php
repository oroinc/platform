<?php

namespace Oro\Bundle\EmbeddedFormBundle\Migrations\Schema;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\MigrationBundle\Migration\Installation;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Oro\Bundle\EmbeddedFormBundle\Migrations\Schema\v1_3\OroEmbeddedFormBundle;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.ExcessiveClassLength)
 */
class OroEmbeddedFormBundleInstaller implements Installation
{
    /**
     * {@inheritdoc}
     */
    public function getMigrationVersion()
    {
        return 'v1_5';
    }

    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        /** Tables generation **/
        $this->createOroEmbeddedFormTable($schema);
        OroEmbeddedFormBundle::addOwner($schema);
    }

    /**
     * Create oro_embedded_form table
     *
     * @param Schema $schema
     */
    protected function createOroEmbeddedFormTable(Schema $schema)
    {
        $table = $schema->createTable('oro_embedded_form');
        $table->addColumn('id', 'string', ['length' => 255]);
        $table->addColumn('title', 'text', []);
        $table->addColumn('css', 'text', []);
        $table->addColumn('form_type', 'string', ['length' => 255]);
        $table->addColumn('success_message', 'text', []);
        $table->addColumn('allowed_domains', 'text', ['notnull' => false]);
        $table->addColumn('created_at', 'datetime', []);
        $table->addColumn('updated_at', 'datetime', []);
        $table->setPrimaryKey(['id']);
    }
}
