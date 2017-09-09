Resetting Symfony Container in consumer
=======================================

Container reset
---------------

As each consumer processes all messages at one thread, there are cases when some services have an internal state
and this state can be changed during processing a message and these changes can affect processing of the next message.

To prevent this problem, before processing a message all services are removed from the dependency injection
container by [ContainerResetExtension](../../Consumption/Extension/ContainerResetExtension.php) extension.
As result, each message is processed by "fresh" state of services.

Persistent processors
---------------------

The removing services from the container may affect the consumer performance dramatically because the initialization
of services may be an expensive operation. This is the reason why the container is not cleared before executing of
some processors that can work correctly with "dirty" state of services. The list of such processors can
be configured by *Resources/config/oro/app.yml* or the application configuration file. Here is an example:

```yaml
oro_message_queue:
    persistent_processors:
        - 'oro_message_queue.client.route_message_processor'
```

This config file inform the [ContainerResetExtension](../../Consumption/Extension/ContainerResetExtension.php) that
the container should not be cleared before executing the `oro_message_queue.client.route_message_processor` processor.

Persistent services
-------------------

As mentioned above, an initialization of some services can take a lot of time. Also some services should not be removed
from the container because it can lead to a crash of the system, the `kernel` is an example of such service.
The list of services that should not be removed from the container can be configured by *Resources/config/oro/app.yml*
or the application configuration file. Here is an example:

```yaml
oro_message_queue:
    persistent_services:
        - 'kernel'
```

Please note that all persistent services must be declared as public services.

Cache state
-----------

The loading of some types of caches may be quite expensive operation. This is the reason why some cache providers
was added to the `persistent_services` list to prevent removing them from the container before processing of a message.

To synchronize such caches between different processes the [CacheState](../../Consumption/CacheState.php) service
is used. The method `renewChangeDate` should be called after a cache is changed. The method `getChangeDate`
returns the last modification time of a cache.

The [InterruptConsumptionExtension](../../Consumption/Extension/InterruptConsumptionExtension.php) uses the `CacheState`
service to check whether a cache is changed. If this happened, the consumer is interrupted after processing
a current message, so the new instance of the consumer will work with the correct cache.
