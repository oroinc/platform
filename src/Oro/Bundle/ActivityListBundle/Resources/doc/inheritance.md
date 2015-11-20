Activity list inheritance targets
=================================

You can add inheritance of activity lists to target entity from some related inheritance target entities. 
It means that in target entities you can see all activity list from general entity and all activity lists
from related entities.

To enable this option you should configure target entity to identify all inheritance 
target entities: use migration extension for adding all necessary configuration to entity config.

Example of migration to enable displaying contact activity lists in appropriate account:

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
