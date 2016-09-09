# DBAL Transport

### Options

```yaml
oro_message_queue:
  transport:
    default: 'dbal'
    dbal:
      connection: default                   # doctrine dbal connection name
      table: oro_message_queue              # table name where messages will be stored
      orphan_time: 300                      # messages considered are orphans after this time in seconds
                                            # (see limitations section for more details)
      polling_interval: 1000                # consumer polling interval in milliseconds
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
`RedeliverOrphanMessagesExtension` which time to time searches for messages which are not acknowledged
for more than orphan time and redelivers these messages. It means execution time of your jobs must be
less than orphan time otherwise you get duplicate message. The default value is 300(5min) but you can
use `orphan_time` option and change value as you wish.
