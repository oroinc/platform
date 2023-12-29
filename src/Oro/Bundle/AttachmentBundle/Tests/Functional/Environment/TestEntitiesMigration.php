<?php

namespace Oro\Bundle\AttachmentBundle\Tests\Functional\Environment;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\ApiBundle\Tests\Functional\Environment\Entity\TestDepartment;
use Oro\Bundle\AttachmentBundle\Migration\Extension\AttachmentExtensionAwareInterface;
use Oro\Bundle\AttachmentBundle\Migration\Extension\AttachmentExtensionAwareTrait;
use Oro\Bundle\EntityExtendBundle\Migration\Extension\ExtendNameGeneratorAwareTrait;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Oro\Bundle\MigrationBundle\Migration\Extension\NameGeneratorAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class TestEntitiesMigration implements
    Migration,
    AttachmentExtensionAwareInterface,
    NameGeneratorAwareInterface
{
    use AttachmentExtensionAwareTrait;
    use ExtendNameGeneratorAwareTrait;

    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries): void
    {
        $this->addAttachmentAssociationToTestDepartment($schema);
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
}
