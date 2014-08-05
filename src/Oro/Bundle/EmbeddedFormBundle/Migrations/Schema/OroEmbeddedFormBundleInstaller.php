<?php

namespace Oro\Bundle\EmbeddedFormBundle\Migrations\Schema;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\MigrationBundle\Migration\Extension\DatabasePlatformAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\Installation;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.ExcessiveClassLength)
 */
class OroEmbeddedFormBundleInstaller implements Installation, DatabasePlatformAwareInterface
{
    /**
     * @var AbstractPlatform
     */
    protected $platform;

    /**
     * {@inheritdoc}
     */
    public function setDatabasePlatform(AbstractPlatform $platform)
    {
        $this->platform = $platform;
    }

    /**
     * {@inheritdoc}
     */
    public function getMigrationVersion()
    {
        return 'v1_4';
    }

    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        /** Tables generation **/
        $this->createOroEmbeddedFormTable($schema);

        if ($this->platform->getName() == 'postgresql') {
            $queries->addPostQuery('CREATE EXTENSION IF NOT EXISTS "uuid-ossp";');
        }
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
        $table->addColumn('created_at', 'datetime', []);
        $table->addColumn('updated_at', 'datetime', []);
        $table->setPrimaryKey(['id']);
    }
}
