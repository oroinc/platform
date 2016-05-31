OroMessageQueue Component
======================

The component incorporates message queue in your application via different transports.
It contains several layers.

The lowest layer is called Transport and provides an abstraction of transport protocol.
The Consumption layer provides tools to consume messages, such as cli command, sigal handling, logging, extensions.
It works on top of transport layer.

The Client layer provides ability to start producing\consuming messages with as less as possible configuration.

Usage
-----

This is a complete example of message producing using only a transport layer:

```php
<?php

use Oro\Component\MessageQueue\Transport\Amqp\AmqpConnection;

$connection = AmqpConnection::createFromConfig($config = []);

$session = $connection->createSession();

$queue = $session->createQueue('aQueue');
$message = $session->createMessage('Something has happened');

$session->createProducer()->send($queue, $message);

$session->close();
$connection->close();
```

This is a complete example of message consuming using only a transport layer:

```php
use Oro\Component\MessageQueue\Transport\Amqp\AmqpConnection;

$connection = AmqpConnection::createFromConfig($config = []);

$session = $connection->createSession();

$queue = $session->createQueue('aQueue');
$consumer = $session->createConsumer($queue);

while (true) {
    if ($message = $consumer->receive()) {
        echo $message->getBody();

        $consumer->acknowledge($message);
    }
}

$session->close();
$connection->close();
```

This is a complete example of message consuming using consumption layer:

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

```php
<?php
use Oro\Component\MessageQueue\Consumption\Extensions;
use Oro\Component\MessageQueue\Consumption\QueueConsumer;
use Oro\Component\MessageQueue\Transport\Amqp\AmqpConnection;

$connection = AmqpConnection::createFromConfig($config = []);

$queueConsumer = new QueueConsumer($connection, new Extensions([]));
$queueConsumer->consume('aQueue', new FooMessageProcessor());

$connection->close();
```
