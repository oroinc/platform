OroTagBundle
============

The `OroTagBundle` provides the tags functionality for different entities.

Entity Configuration
--------------------

Tags can only be enabled for Configurable entities. To enable tags for an entity, use the `@Config` annotation, e.g.:

```
/**
...
 * @Config(
 *      defaultValues={
 *          ...
 *          "tag"={
 *              "enabled"=true
 *          }
 *          ...
 *      }
 * )
...
 */
```

Tags can also be enabled/disabled for an entity in the UI `System->Entities->EntityManagement`(attribute `Tagging`).

**Please note**, [Taggable interface](Entity/Taggable.php) is still supported, but deprecated. If entity implements Taggable interface you can't disable tagging for it in the UI.

Tags in grids
-------------

In case if tags are enabled for an entity, tags filter and tags column will be automatically added to the grid of its
records.
You can use migration to hide tags column or tags filter from the grid by default.
The following example shows it:

``` php
<?php

namespace Acme\Bundle\TestBundle\Migrations\Schema\v1_0;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\EntityExtendBundle\Migration\OroOptions;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class AcmeTestBundle implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $options = new OroOptions();

        // Hide tags column from grid by default.
        $options->set('tag', 'enableGridColumn', false);

        // Hide tags filter from grid by default.
        $options->set('tag', 'enableGridFilter', false);

        $table->addOption(OroOptions::KEY, $options);
    }
}
```

Only tags that have been assigned to records of this entity will be available. The list of tags in the filter is also limited by the access level.

Tags in report builder
----------------------
Tags can be used in reports. If tags are enabled for the entity, virtual field `tags` and virtual relation `tags` will be available in the "Designer" section (Columns, Grouping Columns and Filters).
