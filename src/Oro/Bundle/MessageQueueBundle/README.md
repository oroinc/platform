OroMessageQueue Bundle
===================

The bundle integrates OroMessageQueue component.
It adds easy to use configuration layer, register services and tie them together, register handy cli commands.

Usage
-----

First, you have to configure a transport layer and set one to be default.

```yaml
# app/config/config.yml

oro_message_queue:
    transport:
        default: amqp
        amqp: { host: 'localhost', port: 5672, user: 'guest', password: 'guest', vhost: '/' }
    zero_config: ~
```

Once you configured everything you can start producing messages:

```php
<?php

/** @var Oro\Component\MessageQueue\ZeroConfig\MessageProducer $messageProducer **/
$messageProducer = $container->get('oro_message_queue.zero_config.message_producer');

$messageProducer->sendTo('aFooTopic', 'Something has happened');
```

To consume messages you have to first create a message processor:

```php
<?php
use Oro\Component\MessageQueue\Consumption\MessageProcessor;

class FooMessageProcessor implements MessageProcessor
{
    public function process(Message $message, Session $session)
    {
        echo $message->getBody();

        return self::ACK;
    }
}
```

Register it as a container service and subscribe to the topic:

```yaml
orocrm_channel.async.change_integration_status_processor:
    class: 'FooMessageProcessor'
    tags:
        - { name: 'oro_message_queue.zero_config.message_processor', topicName: 'aFooTopic' }
```

Now you can start consuming messages:

```bash
./app/console oro:message-queue:zeroconfig:consume  -vvv
```