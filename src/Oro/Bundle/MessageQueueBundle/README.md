# OroMessageQueue Bundle

The bundle integrates OroMessageQueue component.
It adds easy to use configuration layer, register services and tie them together, register handy cli commands.

## Usage

First, you have to configure a transport layer and set one to be default.

```yaml
# app/config/config.yml

oro_message_queue:
    transport:
        default: amqp
        amqp: { host: 'localhost', port: 5672, user: 'guest', password: 'guest', vhost: '/' }
    client: ~
```

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
orocrm_channel.async.change_integration_status_processor:
    class: 'FooMessageProcessor'
    tags:
        - { name: 'oro_message_queue.client.message_processor' }
```

Now you can start consuming messages:

```bash
./app/console oro:message-queue:consume
```

_**Note**: Add -vvv to find out what is going while you are consuming messages. There is a lot of valuable debug info there._


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

* MessageProducer - The client's message producer, you will use it all the time to send messages
* MessageProcessorInterface - Each class which does the job has to implement this interface
* TopicSubscriberInterface - Kind of EventSubscriberInterface. It allows you to keep a processing code and topics it is subscribed to in one place.
* MessageConsumeCommand - A command you use to consume messages.
* QueueConsumer - A class that works inside the command and watch for a new message and once it is get it pass it to a message processor.


implement transport interfaces