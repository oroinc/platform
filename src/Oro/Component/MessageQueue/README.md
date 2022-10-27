# OroMessageQueue Component

The component incorporates a message queue in your application via different transports.
It contains several layers.

The lowest layer is called Transport and provides an abstraction of transport protocol.
The Consumption layer provides tools to consume messages, such as cli command, signal handling, logging, extensions.
It works on top of transport layer.

The Client layer provides an ability to start producing/consuming messages with as little configuration as possible.

For more information, refer to the [Message Queue](https://doc.oroinc.com/backend/mq/) topic in the online documentation portal.
