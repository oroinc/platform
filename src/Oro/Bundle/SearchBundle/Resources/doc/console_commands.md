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
> php bin/console oro:search:index OroUserBundle:User 1
Started index update for entities.

```

Multiple entities indexation:
```
> php bin/console oro:search:index "Oro\Bundle\ContactBundle\Entity\Contact" 1 2 3 4 5 6 7 8 9 10
Started index update for entities.

```

oro:search:reindex
------------------

This command performs full reindexation of all entities. It has one optional argument that allows to reindex
only entities of specified type.

Reindexation itself might takes lots of time for big amount of data, so it would be good idea to run it by schedule
(f.e. once a day).

All entities reindexation:
```
> php bin/console oro:search:reindex
Started reindex task for all mapped entities

```

One entity reindexation:
```
> bin/console oro:search:reindex OroUserBundle:User
Started reindex task for "OroUserBundle:User" entity

```

Normally, reindexation is performed immediately after the reindex command
is issued. However, it can also be scheduled to be 
performed in the background by the Message Queue consumers.

Advantages of this mode:
* asynchronous
* can be multithreaded
* scalable

You will need a configured Message Queue and at least one running consumer worker to use this mode.

Please use the following parameter to enable it:
```
> bin/console oro:search:reindex --scheduled

```
