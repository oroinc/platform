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
records, but hidden by default. 
Only tags that have been assigned to records of this entity will be available. The list of tags in the filter is also limited by the access level.

Tags in report builder
----------------------
Tags can be used in reports. If tags are enabled for the entity, virtual relation `tags` will be available in the "Designer" section (Columns, Grouping Columns and Filters).
