# Activity List Inheritance Targets


You can add inheritance of activity lists to the target entity from some related inheritance target entities. 

It means that in target entities, you can see all activity list from the general entity and related entities.

To enable this option, configure the target entity to identify all inheritance target entities: use migration extension to add all necessary configuration to the entity config.

The following is an example of the migration to enable the display of contact activity lists in the appropriate account:

class InheritanceActivityTargets implements Migration, ActivityListExtensionAwareInterface
{
    /** @var ActivityListExtension */
    protected $activityListExtension;

    /** {@inheritdoc} */
    public function setActivityListExtension(ActivityListExtension $activityListExtension)
    {
        $this->activityListExtension = $activityListExtension;
    }

    /** {@inheritdoc} */
    public function up(Schema $schema, QueryBag $queries)
    {
        $activityListExtension->addInheritanceTargets($schema, 'orocrm_account', 'orocrm_contact', ['accounts']);
    }
}

Method parameters:
addInheritanceTargets(Schema $schema, $targetTableName, $inheritanceTableName, $path)
string $targetTableName - Target entity table name
string $inheritanceTableName - Inheritance entity table name
string[] $path - Path of relations to target entity
