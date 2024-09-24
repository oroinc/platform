<?php

namespace Oro\Bundle\AttachmentBundle\Migrations\Schema\v1_5;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\EntityExtendBundle\Migration\Extension\ExtendExtensionAwareInterface;
use Oro\Bundle\EntityExtendBundle\Migration\Extension\ExtendExtensionAwareTrait;
use Oro\Bundle\EntityExtendBundle\Migration\Extension\ExtendNameGeneratorAwareTrait;
use Oro\Bundle\InstallerBundle\Migration\RenameExtendedManyToOneAssociation20;
use Oro\Bundle\MigrationBundle\Migration\ConnectionAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\ConnectionAwareTrait;
use Oro\Bundle\MigrationBundle\Migration\Extension\NameGeneratorAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\Extension\RenameExtensionAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\Extension\RenameExtensionAwareTrait;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class RenameAttachmentAssociation implements
    Migration,
    RenameExtensionAwareInterface,
    ExtendExtensionAwareInterface,
    ConnectionAwareInterface,
    NameGeneratorAwareInterface
{
    use ConnectionAwareTrait;
    use ExtendExtensionAwareTrait;
    use RenameExtensionAwareTrait;
    use ExtendNameGeneratorAwareTrait;

    #[\Override]
    public function up(Schema $schema, QueryBag $queries)
    {
        $helper = new RenameExtendedManyToOneAssociation20(
            $this->connection,
            $this->nameGenerator,
            $this->renameExtension,
            $this->extendExtension
        );
        $helper->rename($schema, $queries, 'Oro\Bundle\AttachmentBundle\Entity\Attachment', null);
    }
}
