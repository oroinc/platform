# Delayed Messages

Delaying a message is a way to send messages to a broker and process them after a certain period (e.g., in 10 seconds).
To delay a message, publish it with the property `delay` which takes an integer representing the number of milliseconds the message should be delayed by.
Use the `Oro\Component\MessageQueue\Client\Message::setDelay` method to do it.
Once the delay expires, the message can be consumed.

## Delayed Message Example

```php
    $message = new Message([]);
    $message->setDelay(10); // message will be consumed after 10 seconds

    $this->messageProducer->send(Topics::MESSAGE_TOPIC, $message);
```

## Redelivery Process

To make sure that a message is delivered even if the messaging system fails, the messaging system implements **Guaranteed Delivery**.
This way the message is not lost even if the messaging system crashes.
The message processor can return a **REQUEUE** result, is which case the message is returned to the message broker on top of the stack.
The same behavior can be reached if an error occurrs during message processing.
To prevent blocking of the consumer (for example, if the message crashes each time with an error in the loop), delayed redelivery process is implemented and enabled by default.
`Oro\Bundle\MessageQueueBundle\Consumption\Extension\RedeliveryMessageExtension` is responsible for this logic.
It copies data from redelivered message, creates a new one with copied data, sets `delay` (by default _10 seconds_) and sends it to the message broker.
After that, the old message is **REJECTED**.

### Redelivery Message Configuration

Run `bin/console config:dump oro_message_queue` command to see message queue configurations.

```yml
# Default configuration for extension with alias: "oro_message_queue"
oro_message_queue:

    # Consumption client configuration.
    client:
        # Redelivery message extension configuration.
        redelivery:

            # If redelivery enabled than new copied message will be published
            # to message broker and old one will be REJECTED when error
            # was occurred during message processing.
            enabled:              true

            # Time through which message will be re-published to the broker,
            # old one will be REJECTED immediately.
            delay_time:           10

```

Example how to change redelivery delay time:

```yml
# config/config_prod.yml

oro_message_queue:
    client:
        redelivery: { delay_time: 10 }
```
