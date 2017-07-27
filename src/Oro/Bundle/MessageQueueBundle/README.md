# OroMessageQueue Bundle

## Table of Contents

 - [Overview](#overview)
 - [Usage](#usage)
 - [Cunsomer options](#cunsomer-options)
 - [Supervisord](#supervisord)
 - [Internals](#internals)
   - [Structure](#structure)
   - [Flow](#flow)
   - [Custom transport](#custom-transport)
   - [Key Classes](#key-classes)
 - [Unit and Functional tests](#unit-and-functional-tests)

## Overview

The bundle integrates OroMessageQueue component.
It adds easy to use configuration layer, register services and tie them together, register handy cli commands.

## Jobs

The bundle provides an entity and a web gui for [the jobs](../../Component/MessageQueue/README.md#jobs). So the jobs are created in the db and have
a web gui where you can monitor jobs status and interrupt jobs.


## Usage

First, you have to configure a transport layer and set one to be default. For the config settings

```yaml
# app/config/config.yml

oro_message_queue:
    transport:
        default: '%message_queue_transport%'
        '%message_queue_transport%': '%message_queue_transport_config%'
    client: ~
```

we can configure one of the supported transports via parameters:

### DBAL transport 

```yaml
# app/config/parameters.yml

    message_queue_transport: DBAL
    message_queue_transport_config: ~
```
[DBAL transport options](./Resources/doc/dbal.md)

Once you configured everything you can start producing messages:

```php
<?php

/** @var Oro\Component\MessageQueue\Client\MessageProducer $messageProducer **/
$messageProducer = $container->get('oro_message_queue.message_producer');

$messageProducer->send('aFooTopic', 'Something has happened');
```

To consume messages you have to first create a message processor:

```php
<?php
use Oro\Component\MessageQueue\Consumption\MessageProcessor;

class FooMessageProcessor implements MessageProcessor, TopicSubscriberInterface
{
    public function process(Message $message, Session $session)
    {
        echo $message->getBody();

        return self::ACK;
        // return self::REJECT; // when the message is broken
        // return self::REQUEUE; // the message is fine but you want to postpone processing
    }

    public static function getSubscribedTopics()
    {
        return ['aFooTopic'];
    }
}
```

Register it as a container service and subscribe to the topic:

```yaml
oro_channel.async.change_integration_status_processor:
    class: 'FooMessageProcessor'
    tags:
        - { name: 'oro_message_queue.client.message_processor' }
```

Now you can start consuming messages:

```bash
./app/console oro:message-queue:consume
```

_**Note**: Add -vvv to find out what is going while you are consuming messages. There is a lot of valuable debug info there._

### Consumer options

* `--message-limit=MESSAGE-LIMIT`                Consume n messages and exit
* `--time-limit=TIME-LIMIT`                      Consume messages during this time
* `--memory-limit=MEMORY-LIMIT`                  Consume messages until process reaches this memory limit in MB

The `--memory-limit` option is recommended for the normal consumer usage. If the option is set a consumer checks
the used memory amount after each message processing and terminates if it is exceeded. For example if a consumer
was run:

```bash
./app/console oro:message-queue:consume --memory-limit=700
``` 

then:

* The consumer processing a message
* The cunsomer checks the used memory amount
* If it exceeds the option value (i.e. 705 MB or 780Mb or 1300 Mb) the consumer terminates (and Supervisord re-runs it)
* Otherwise it continues message processing.
 
We recommend to always set this option to the value 2-3 times less than php memory limit. It will help to avoid php memory 
limit error during message processing.

We recommend to set the `--time-limit` option to 5-10 minutes if using the `DBAL` transport to avoid database connection issues 

### Consumer interruption

Consumers can normally interrupt the message procession by many reasons:

* Out of memory (if the option is set)
* Timeout (if the option is set)
* Messages limit exceeded (if the option is set)
* Forcefully by an event:
  * If a cache was cleared
  * If a schema was updated
  * If a maintenance mode was turned off
  
The normal interruption occurs only after a message was processed. If an event was fired during a message processing a 
consumer completes the message processing and interrupts after the processing is done.
  
Also a consumer interrupts **if an exception was thrown during a message processing**. 

### Supervisord

As you read before consumers can normally interrupt the message procession by many reasons.
In the all cases above the interrupted consumer should be re-run. So you must keep running 
`oro:message-queue:consume` command and to do this best we advise you to delegate this responsibility 
to [Supervisord](http://supervisord.org/). With next program configuration supervisord keeps running 
four simultaneous instances of `oro:message-queue:consume` command and cares about relaunch if instance 
has dead by any reason.

```ini
[program:oro_message_consumer]
command=/path/to/app/console --env=prod --no-debug oro:message-queue:consume
process_name=%(program_name)s_%(process_num)02d
numprocs=4
autostart=true
autorestart=true
startsecs=0
user=apache
redirect_stderr=true
```

## Internals

### Structure

You can skip it if you are only going to use the component.
The component is split into several layers:

* **Transport** - The transport API provides a common way for programs to create, send, receive and read messages. Inspired by [Java Message Service](https://docs.oracle.com/javaee/1.4/api/javax/jms/package-summary.html)
* **Router** - An implementation of [RecipientList](http://www.enterpriseintegrationpatterns.com/patterns/messaging/RecipientList.html) pattern.
* **Consumption** - the layer provides tools to simplify consumption of messages. It provides a cli command, a queue consumer, message processor and ways to extend it.
* **Client** - provides a high level abstraction. It provides easy to use abstraction for producing and processing messages. It also reduces a need to configure a broker.

![Component structure](./Resources/doc/component_structure_diagram.png "The Oro MessageQueue component structure")

### Flow

The client's message producer sends a message to a router message processor.
It takes the message and search for real recipients who is interested in such a message.
Then, It sends a copy of a message for all of them.
Each target message processor takes its copy of the message and process it.

![Message flow](./Resources/doc/message_flow_diagram.png "The message flow")

The message itself has headers and body and they change this way while traveling through the system:

![Message structure](./Resources/doc/message_structure_diagram.png "The message structure")

### Custom transport

If you happen to need to implement a custom provider take a look at transport's interfaces.
You have to provide an implementation for them

### Key Classes

* [MessageProducer](../../Component/MessageQueue/Client/MessageProducer.php) - The client's message producer, you will use it all the time to send messages
* [MessageProcessorInterface](../../Component/MessageQueue/Consumption/MessageProcessorInterface.php) - Each class which does the job has to implement this interface
* [TopicSubscriberInterface](../../Component/MessageQueue/Client/TopicSubscriberInterface.php) - Kind of EventSubscriberInterface. It allows you to keep a processing code and topics it is subscribed to in one place.
* [MessageConsumeCommand](../../Component/MessageQueue/Client/ConsumeMessagesCommand.php) - A command you use to consume messages.
* [QueueConsumer](../../Component/MessageQueue/Consumption/QueueConsumer.php) - A class that works inside the command and watch for a new message and once it is get it pass it to a message processor.

## Unit and Functional tests

To test that a message was sent in unit and functional tests, you can use `MessageQueueExtension` trait. There are two implementation of this trait, one for unit tests, another for functional tests:

- [Oro\Bundle\MessageQueueBundle\Test\Unit\MessageQueueExtension](./Test/Unit/MessageQueueExtension.php) for unit tests
- [Oro\Bundle\MessageQueueBundle\Test\Functional\MessageQueueExtension](./Test/Functional/MessageQueueExtension.php) for functional tests

Also, in case if you need custom logic for manage sent messages, you can use [Oro\Bundle\MessageQueueBundle\Test\Unit\MessageQueueAssertTrait](./Test/Unit/MessageQueueAssertTrait.php) or [Oro\Bundle\MessageQueueBundle\Test\Functional\MessageQueueAssertTrait](./Test/Functional/MessageQueueAssertTrait.php) traits. 

Before you start to use traits in functional tests, you need to register `oro_message_queue.test.message_collector` service for `test` environment.

```yaml
# app/config/config_test.yml

services:
    oro_message_queue.test.message_collector:
        class: Oro\Bundle\MessageQueueBundle\Test\Functional\MessageCollector
        decorates: oro_message_queue.client.message_producer
        arguments:
            - '@oro_message_queue.test.message_collector.inner'
```

The following example shows how to test whether a message was sent.

```php
<?php
namespace Acme\Bundle\AcmeBundle\Tests\Functional;

use Oro\Bundle\MessageQueueBundle\Test\Functional\MessageQueueExtension;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class SomeTest extends WebTestCase
{
    use MessageQueueExtension;

    public function testSingleMessage()
    {
        // assert that a message was sent to a topic
        self::assertMessageSent('aFooTopic', 'Something has happened');

        // assert that at least one message was sent to a topic
        // can be used if a message is not matter
        self::assertMessageSent('aFooTopic');
    }

    public function testSeveralMessages()
    {
        // assert that exactly given messages were sent to a topic
        self::assertMessagesSent(
            'aFooTopic',
            [
                'Something has happened',
                'Something else has happened',
            ]
        );
        // assert that the exactly given number of messages were sent to a topic
        // can be used if messages are not matter
        self::assertMessagesCount('aFooTopic', 2);
        // also assertCountMessages alias can be used to do the same assertion
        self::assertCountMessages('aFooTopic');
    }

    public function testNoMessages()
    {
        // assert that no any message was sent to a topic
        self::assertMessagesEmpty('aFooTopic');
        // also assertEmptyMessages alias can be used to do the same assertion
        self::assertEmptyMessages('aFooTopic');
    }

    public function testAllMessages()
    {
        // assert that exactly given messages were sent
        // NOTE: use this assertion with caution because it is possible
        // that messages not related to a testing functionality were sent as well
        self::assertAllMessagesSent(
            [
                ['topic' => 'aFooTopic', 'message' => 'Something has happened'],
                ['topic' => 'aFooTopic', 'message' => 'Something else has happened'],
            ]
        );
    }
}
```

In unit tests you are usually need to pass the message producer to a service you test. To fetch correct instance of message producer in the unit tests use `self::getMessageProducer()`, e.g.:

```php
<?php
namespace Acme\Bundle\AcmeBundle\Tests\Unit;

use Acme\Bundle\AcmeBundle\SomeClass;
use Oro\Bundle\MessageQueueBundle\Test\Unit\MessageQueueExtension;

class SomeTest extends \PHPUnit_Framework_TestCase
{
    use MessageQueueExtension;

    public function testSingleMessage()
    {
        $instance = new SomeClass(self::getMessageProducer());
        
        $instance->doSomethind();

        self::assertMessageSent('aFooTopic', 'Something has happened');
    }
}
```
