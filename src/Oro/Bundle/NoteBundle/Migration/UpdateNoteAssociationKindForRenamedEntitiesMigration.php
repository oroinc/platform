<?php

namespace Oro\Bundle\NoteBundle\Migration;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\ActivityBundle\Migration\Extension\ActivityExtension;
use Oro\Bundle\ActivityBundle\Migration\Extension\ActivityExtensionAwareInterface;
use Oro\Bundle\EntityExtendBundle\Migration\Extension\ExtendExtension;
use Oro\Bundle\EntityExtendBundle\Migration\Extension\ExtendExtensionAwareInterface;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendDbIdentifierNameGenerator;
use Oro\Bundle\MigrationBundle\Migration\Extension\NameGeneratorAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\OrderedMigrationInterface;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Oro\Bundle\MigrationBundle\Tools\DbIdentifierNameGenerator;

abstract class UpdateNoteAssociationKindForRenamedEntitiesMigration implements
    Migration,
    ExtendExtensionAwareInterface,
    ActivityExtensionAwareInterface,
    NameGeneratorAwareInterface,
    OrderedMigrationInterface
{
    /**
     * @var ActivityExtension
     */
    protected $activityExtension;
    /**
     * @var ExtendExtension
     */
    protected $extendExtension;

    /**
     * @var ExtendDbIdentifierNameGenerator
     */
    protected $nameGenerator;

    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $updateNoteAssociationKindQuery = new UpdateNoteAssociationKindQuery(
            $schema,
            $this->activityExtension,
            $this->extendExtension,
            $this->nameGenerator
        );
        $updateNoteAssociationKindQuery->registerOldClassNames($this->getRenamedEntitiesNames($schema));
        $queries->addPostQuery($updateNoteAssociationKindQuery);
    }

    /**
     * @param Schema $schema
     *
     * @return array [current class name => old class name, ...]
     */
    abstract protected function getRenamedEntitiesNames(Schema $schema);

    /**
     * {@inheritdoc}
     */
    public function setActivityExtension(ActivityExtension $activityExtension)
    {
        $this->activityExtension = $activityExtension;
    }

    /**
     * {@inheritdoc}
     */
    public function setExtendExtension(ExtendExtension $extendExtension)
    {
        $this->extendExtension = $extendExtension;
    }

    /**
     * {@inheritdoc}
     */
    public function setNameGenerator(DbIdentifierNameGenerator $nameGenerator)
    {
        $this->nameGenerator = $nameGenerator;
    }

    /**
     * Notes should be migrated after all tables rename migration will be executed
     *
     * {@inheritdoc}
     */
    public function getOrder()
    {
        return 200;
    }
}
