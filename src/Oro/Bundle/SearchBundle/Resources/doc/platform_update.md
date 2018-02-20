Reindexation during platform update
===================================

Note that when you execute `oro:platform:update` command, as a part of update process it performs full reindexation of all the affected entities in the foreground.
To avoid this, you can use the `--schedule-search-reindexation` and `--skip-search-reindexation` options that were added to the  `oro:platform:update` command by this bundle.

####`--schedule-search-reindexation`
This option allows you to postpone full reindexation. In this case, the reindexation command will be added into the message queue and will be executed later, when message queue consumers will be started.
See [MessageQueueBundle documentation](../../../MessageQueueBundle/README.md)
 
####`--skip-search-reindexation`
This option allows to completely skip reindexation during update process.
