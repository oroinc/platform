# Logging, Error Handling and Debugging

In the process of consuming message queue, you will inevitably encounter unforeseen errors.
See below the list of key highlights how to handle errors, work with logs and look if something does not work as planned during message processing.

## Table of Contents

* [Logs, Output and Verbosity](#logs-output-and-verbosity)
* [Consumer Heartbeat](#consumer-heartbeat)
* [Consumer Interruption](#consumer-interruption)
* [Errors and Crashes](#errors-and-crashes)
* [Profiling](#profiling)

## Logs, Output and Verbosity

Message Queue Consumer uses [MonologBundle](https://github.com/symfony/monolog-bundle) to output Logs.
To output message with any of logging levels/priorities you should inject **LoggerInterface** in your *processor*
 and log error the same way as it described in ["Logging with Monolog" Symfony doc](http://symfony.com/doc/current/logging.html#logging-a-message)
Consumer console command have different [verbosity levels](https://symfony.com/doc/current/console/verbosity.html), which determine the messages displayed in the output.

Console option    | Output Errors
----------------- | ------------------------------
`-q` or `--quiet` | `LogLevel::ERROR` and higher
(none)            | `LogLevel::WARNING` and higher
`-v`              | `LogLevel::NOTICE` and higher
`-vv`             | `LogLevel::INFO` and higher
`-vvv`            | `LogLevel::DEBUG` and higher

All logs `LogLevel::ERROR` and higher also will be printed to the `prod.log` file. 
You can change minimal log level that should be printed to the `prod.log` file using command `oro:logger:level`,
 for more details read doc ["Temporarily Decrease Log Level"](../../../LoggerBundle/README.md#temporarily-decrease-log-level).

### How to Add extra Data to Log Messages via a Processor

Sometimes it is necessary to add own data to log extra data. Create own processor and add `monolog.processor` DIC tag to it.
 See more in the [doc](https://symfony.com/doc/current/logging/processors.html).

### Handlers: Writing Logs to different Locations

Consumer output is based on [Monolog](https://github.com/Seldaek/monolog), so it support stack of handlers, each can be used to write the log entries to different locations (e.g. files, database, Slack, etc).
 See more in the [doc](https://symfony.com/doc/current/logging.html#handlers-writing-logs-to-different-locations).
 
It is useful when your production is configured with real-time log service such as [Stackdriver](https://cloud.google.com/stackdriver/), 
 [Kibana](https://www.elastic.co/products/kibana) and etc.
 
* [Writing Logs to Stackdriver](./stackdriver.md)

## Consumer Heartbeat

An administrator must be informed about the state of consumers in the system (whether there is at least one alive). 

This is covered by the Consumer Heartbeat functionality that works in the following way:

- On start and after every configured time period, each consumer calls the `tick` method of the [ConsumerHeartbeat](./Consumption/ConsumerHeartbeat.php)
service that informs the system that the consumer is alive.
- The cron command [oro:cron:message-queue:consumer_heartbeat_check](./Command/ConsumerHeartbeatCommand.php)
is periodically executed to check consumers' state. If it does not find any alive consumers, the `oro/message_queue_state`
socket message is sent notifying all logged-in users that the system may work incorrectly (because consumers are not available).
- The same check is also performed when a user logs in. This is done to notify users about the problem as soon as possible.                                 
                     
The check period can be changed in the application configuration file using the `consumer_heartbeat_update_period` option:

```yml
oro_message_queue:
    consumer:
        heartbeat_update_period: 20 #the update period was set to 20 minutes 

```                     

The default value of the `heartbeat_update_period` option is 15 minutes.

To disable the Consumer Heartbeat functionality, set the `heartbeat_update_period` option to 0.

## Consumer Interruption

During the consuming and processing message sometimes it is necessary to interrupt consumer, to avoid such cases as 
 **not actual cached data**, **maintenance mode** or **memory leaks**, also limit messages or processing time during **debugging**
 or any other reason when consumer should be stopped. Below is a list of friendly consumer interruption:

Output                                                                                   | Description
---------------------------------------------------------------------------------------- | ---------------------------------------------------------------------------------
`app.WARNING: Consuming interrupted, reason: Interrupt execution.`                       | Consumer was interrupted with stop signal: `SIGTERM`, `SIGQUIT` or `SIGINT`
`app.WARNING: Consuming interrupted, reason: The limit time has passed.`                 | Passed time limit configured with command option `--time-limit`
`app.WARNING: Consuming interrupted, reason: The message limit reached.`                 | Passed message limit configured with command option `--message-limit`
`app.WARNING: Consuming interrupted, reason: The memory limit reached.`                  | Passed time limit configured with command option `--memory-limit`
`app.WARNING: Consuming interrupted, reason: The cache was cleared.`                     | Cache was cleared (it also will be triggered after saving "System Configuration"), more details [here](./container_in_consumer.md#cache-state).
`app.WARNING: Consuming interrupted, reason: The cache was invalidated.`                 | Schema was updated and cache was cleared
`app.WARNING: Consuming interrupted, reason: The Maintenance mode has been deactivated.` | Maintenance mode was turned off

The normal interruption occurs only after a message was processed. If an event was fired during a message processing a 
consumer completes the message processing and interrupts after the processing is done.

### Example how to interrupt consumer from own extension

Create consumption extension with own logic:

```php
<?php
// src/Acme/Bundle/DemoBundle/Consumption/Extension/CustomExtension.php

namespace Oro\Component\MessageQueue\Consumption\Extension;

use Oro\Component\MessageQueue\Consumption\AbstractExtension;
use Oro\Component\MessageQueue\Consumption\Context;

class CustomExtension extends AbstractExtension
{
    /**
     * {@inheritdoc}
     */
    public function onPostReceived(Context $context)
    {
        // ... own logic

        if (!$context->isExecutionInterrupted()) {
            $context->setExecutionInterrupted(true);
            $context->setInterruptedReason('Message with reason of interruption.');
        }
    }
}
```

Declare service:

```yaml
# src/Acme/Bundle/DemoBundle/Resources/config/services.yml

services:
    acme_demo.consumption.custom_extension:
        class: 'Acme\Bundle\DemoBundle\Consumption\Extension\CustomExtension'
        public: false
        tags:
            - { name: 'oro_message_queue.consumption.extension', persistent: true }
```

## Errors and Crashes

When application is working and consumer is configured, it's likely that there will be some unforeseen errors. 
A few example of common errors that may occur in the course of your application's daily operations, are listed below:

* Database related errors (connection errors, accessing errors, query errors, data errors)
* File system errors (permission errors, no disk space errors)
* Third-party integrations errors

If one listed errors is occur, processor will return **REQUEUE** and message will be redelivered.

## Profiling

Below is a list of key variables that was added to **extra** and will be shown in the output.

Variable             | Description
-------------------- | ------------------------------
`extension`          | Extension class in the context of which was caused by log message
`processor`          | Processor that process queue message
`message_id`         | Unique message ID
`message_body`       | Message body
`message_properties` | List of message properties that were received from message broker
`message_headers`    | List of message headers that were received from message broker
`message_priority`   | Message priority (responsible for the order in which messages are processed)
`memory_usage`       | Current memory usage
`memory_taken`       | Memory usage difference (current memory usage minus memory usage at the beginning of processing current message).
`peak_memory`        | Peak memory usage (maximum value of `memory_usage` from all previous log records related to processing current message).
`elapsed_time`       | Time passed since the consumer started processing current message

To add own variables to **extra** that should be shown in the output read the [doc](#how-to-add-extra-data-to-log-messages-via-a-processor).
