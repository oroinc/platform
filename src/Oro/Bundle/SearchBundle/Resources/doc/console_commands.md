Console commands
================

OroSearchBundle provides several console commands to interact with search index.

oro:search:index
----------------

This command performs indexation of specified entities. Command required two arguments - class name
(short notation or FQCN) and list of identifiers (at least one is required). If specified entity exists
then corresponding search index data will be updated, if not - index data will be removed. This command is used
for queued indexation.

Single entity indexation:
```
> php app/console oro:search:index OroUserBundle:User 1
Entities successfully updated in index

```

Multiple entities indexation:
```
> php app/console oro:search:index "OroCRM\Bundle\ContactBundle\Entity\Contact" 1 2 3 4 5 6 7 8 9 10
Entities successfully updated in index

```

oro:search:reindex
------------------

This command performs full reindexation of all entities. It has one optional argument that allows to reindex
only entities of specified type.

Reindexation itself might takes lots of time for big amount of data, so it would be good idea to run it by schedule
(f.e. once a day).

All entities reindexation:
```
> php app/console oro:search:reindex
Starting reindex task for all mapped entities
Total indexed items: 733
```

One entity reindexation:
```
> app/console oro:search:reindex OroUserBundle:User
Starting reindex task for "OroUserBundle:User" entity
Total indexed items: 53

```
