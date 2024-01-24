<?php

namespace Oro\Bundle\AttachmentBundle\Tests\Functional\Environment;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\ApiBundle\Tests\Functional\Environment\Entity\TestDepartment;
use Oro\Bundle\AttachmentBundle\Migration\Extension\AttachmentExtension;
use Oro\Bundle\AttachmentBundle\Migration\Extension\AttachmentExtensionAwareInterface;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendDbIdentifierNameGenerator;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Oro\Bundle\MigrationBundle\Migration\Extension\NameGeneratorAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Oro\Bundle\MigrationBundle\Tools\DbIdentifierNameGenerator;

class TestEntitiesMigration implements
    Migration,
    AttachmentExtensionAwareInterface,
    NameGeneratorAwareInterface
{
    private AttachmentExtension $attachmentExtension;
    private ExtendDbIdentifierNameGenerator $nameGenerator;

    /**
     * {@inheritDoc}
     */
    public function setAttachmentExtension(AttachmentExtension $attachmentExtension): void
    {
        $this->attachmentExtension = $attachmentExtension;
    }

    /**
     * {@inheritDoc}
     */
    public function setNameGenerator(DbIdentifierNameGenerator $nameGenerator): void
    {
        $this->nameGenerator = $nameGenerator;
    }

    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries): void
    {
        $this->addAttachmentAssociationToTestDepartment($schema);
        $this->createTestAttachmentOwnerTable($schema);
    }

    private function addAttachmentAssociationToTestDepartment(Schema $schema): void
    {
        $associationColumnName = $this->nameGenerator->generateRelationColumnName(
            ExtendHelper::buildAssociationName(TestDepartment::class),
            '_id'
        );
        if (!$schema->getTable('oro_attachment')->hasColumn($associationColumnName)) {
            $this->attachmentExtension->addAttachmentAssociation($schema, 'test_api_department', ['image/*']);
        }
    }

    private function createTestAttachmentOwnerTable(Schema $schema): void
    {
        if ($schema->hasTable('test_api_attachment_owner')) {
            return;
        }

        $table = $schema->createTable('test_api_attachment_owner');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('name', 'string', ['length' => 255]);
        $table->setPrimaryKey(['id']);

        $this->attachmentExtension->addFileRelation($schema, 'test_api_attachment_owner', 'test_file');
    }
}
