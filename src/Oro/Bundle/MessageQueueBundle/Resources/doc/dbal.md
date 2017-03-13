# DBAL Transport

### Options

```yaml
oro_message_queue:
  transport:
    default: 'dbal'
    dbal:
      connection: default                  # doctrine dbal connection name
      table: oro_message_queue             # table name where messages will be stored
      pid_file_dir: /tmp/oro-message-queue # RedeliverOrphanMessagesExtension stores consumer pid files here
      consumer_process_pattern: ':consume' # used by RedeliverOrphanMessagesExtension to check the working or non-working consumers
                                           # (see limitations section for more details)
      polling_interval: 1000               # consumer polling interval in milliseconds
                                           # (see limitations section for more details)
```

### Limitations

As RDBMS are not designed to work as message queue implementation has several limitations.

* There is no way to use event-driven model and listen for new inserts into DB. We use polling
model to ask DB it has new messages. We run such queries ones per second by default and it means
every consumer receives only one message per second. Use `polling_interval` option to change this
value but low interval values may cause DB load

* When consumer receives message it updates DB record with unique identifier so any other consumer
cant receive this message. After job is done and message is acknowledged consumer removes this record
from the DB. This is a success story but sometimes error happens and is possible when we got fatal error
consumer process is dead now but "locked" message are still in the DB. For such cases there is
`RedeliverOrphanMessagesExtension` which time to time searches for messages which are consumed but not
acknowledged and redelivers these messages.
