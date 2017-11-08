Transaction watchers for default DBAL connection
================================================

Sometimes it is required to perform some work only after data are commited to the database. For instance, sending
notifications to users or to external systems.

In this case you can create a class implements `Oro\Component\DoctrineUtils\DBAL\TransactionWatcherInterface`
and register it as a service tagged by `oro.doctrine.connection.transaction_watcher` tag. After that this class
will be able to perform some actions after the root transaction for default DBAL connection starts, commited
or rolled back. Please note that methods of this class will be called for the root transaction only; starting,
commiting and rolling back of any nested transaction are not tracked.
