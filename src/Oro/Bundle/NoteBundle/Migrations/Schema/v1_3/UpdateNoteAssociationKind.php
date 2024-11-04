<?php

namespace Oro\Bundle\NoteBundle\Migrations\Schema\v1_3;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\ActivityBundle\Migration\Extension\ActivityExtensionAwareInterface;
use Oro\Bundle\ActivityBundle\Migration\Extension\ActivityExtensionAwareTrait;
use Oro\Bundle\EntityExtendBundle\Migration\Extension\ExtendExtensionAwareInterface;
use Oro\Bundle\EntityExtendBundle\Migration\Extension\ExtendExtensionAwareTrait;
use Oro\Bundle\EntityExtendBundle\Migration\Extension\ExtendNameGeneratorAwareTrait;
use Oro\Bundle\MigrationBundle\Migration\Extension\NameGeneratorAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Oro\Bundle\NoteBundle\Migration\UpdateNoteAssociationKindQuery;

class UpdateNoteAssociationKind implements
    Migration,
    ExtendExtensionAwareInterface,
    ActivityExtensionAwareInterface,
    NameGeneratorAwareInterface
{
    use ExtendExtensionAwareTrait;
    use ActivityExtensionAwareTrait;
    use ExtendNameGeneratorAwareTrait;

    #[\Override]
    public function up(Schema $schema, QueryBag $queries)
    {
        $query = new UpdateNoteAssociationKindQuery(
            $schema,
            $this->activityExtension,
            $this->extendExtension,
            $this->nameGenerator
        );
        $queries->addPostQuery($query);
    }
}
