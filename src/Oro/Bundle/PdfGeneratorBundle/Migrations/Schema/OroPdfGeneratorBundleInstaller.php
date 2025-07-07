<?php

declare(strict_types=1);

namespace Oro\Bundle\PdfGeneratorBundle\Migrations\Schema;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Installation;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

final class OroPdfGeneratorBundleInstaller implements Installation
{
    #[\Override]
    public function getMigrationVersion(): string
    {
        return 'v6_1_3_0';
    }

    #[\Override]
    public function up(Schema $schema, QueryBag $queries): void
    {
        $this->createPdfDocumentTable($schema);
    }

    private function createPdfDocumentTable(Schema $schema): void
    {
        $table = $schema->createTable('oro_pdf_generator_pdf_document');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('uuid', 'guid', ['notnull' => true, 'unique' => true]);
        $table->addColumn('pdf_document_name', 'string', ['length' => 255, 'notnull' => true]);
        $table->addColumn('pdf_document_type', 'string', ['length' => 255, 'notnull' => true]);
        $table->addColumn('pdf_document_file_id', 'integer', ['notnull' => false]);
        $table->addColumn('source_entity_class', 'string', ['length' => 255, 'notnull' => false]);
        $table->addColumn('source_entity_id', 'integer', ['notnull' => false]);
        $table->addColumn('pdf_document_payload', 'json', ['notnull' => false]);
        $table->addColumn('pdf_options_preset', 'string', ['notnull' => true]);
        $table->addColumn('pdf_document_state', 'string', ['length' => 255, 'notnull' => true]);
        $table->addColumn('pdf_document_generation_mode', 'string', ['length' => 255, 'notnull' => true]);
        $table->addColumn('organization_id', 'integer', ['notnull' => false]);
        $table->addColumn('created_at', 'datetime', ['notnull' => true]);
        $table->addColumn('updated_at', 'datetime', ['notnull' => true]);

        $table->setPrimaryKey(['id']);
        $table->addUniqueIndex(['uuid'], 'oro_pdf_generator_pdf_document_uuid_uidx');
        $table->addUniqueIndex(['pdf_document_file_id'], 'oro_pdf_generator_pdf_document_file_uidx');
        $table->addIndex(
            ['pdf_document_name', 'pdf_document_type', 'source_entity_class', 'source_entity_id'],
            'oro_pdf_generator_pdf_document_idx'
        );
        $table->addForeignKeyConstraint(
            'oro_attachment_file',
            ['pdf_document_file_id'],
            ['id'],
            ['onDelete' => 'SET NULL']
        );
        $table->addForeignKeyConstraint('oro_organization', ['organization_id'], ['id'], ['onDelete' => 'SET NULL']);
    }
}
