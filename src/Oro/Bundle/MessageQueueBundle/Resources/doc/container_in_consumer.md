Resetting Symfony Container in consumer
=======================================

Container reset
---------------

As each consumer processes all messages at one thread, there are cases when some services have an internal state
and this state can be changed during processing a message and these changes can affect processing of the next message.

To prevent this problem, after processing a message all services are removed from the dependency injection
container by [ContainerResetExtension](../../Consumption/Extension/ContainerResetExtension.php) extension.
As result, each message is processed by "fresh" state of services. See [persistent processors](#persistent-processors)
and [persistent services](#persistent-services) sections if you want to change this behaviour.

If it is required to perform additional actions before the container reset, you can create a class implements
[ClearerInterface](../../Consumption/Extension/ClearerInterface.php) and register it in the container with
tag `oro_message_queue.consumption.clearer`. The `priority` attribute can be used to change the execution order
of your clearer. The higher the priority, the earlier the clearer is executed.


Persistent processors
---------------------

The removing services from the container may affect the consumer performance dramatically because the initialization
of services may be an expensive operation. This is the reason why the container is not cleared after executing of
some processors that can work correctly with "dirty" state of services. The list of such processors can
be configured by *Resources/config/oro/app.yml* or the application configuration file. Here is an example:

```yaml
oro_message_queue:
    persistent_processors:
        - 'oro_message_queue.client.route_message_processor'
```

This config file inform the [ContainerResetExtension](../../Consumption/Extension/ContainerResetExtension.php) that
the container should not be cleared after executing the `oro_message_queue.client.route_message_processor` processor.

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

Please note that all persistent services must be declared as public services; otherwise, they will be ignored.

Persistent consumption extensions
---------------------------------

By default all consumption extensions are recreated each time after resetting of the container. But this can be
changed, for example by performance reasons or because some extensions may have an internal state that should
keep unchanged even if the container is reset. To prevent recreation of an extension just mark it by
`persistent` attribute of the tag `oro_message_queue.consumption.extension`. Here is an example:

```yaml
    acme.consumption.my_extension:
        class: Acme\Bundle\AppBundle\Async\Consumption\Extension\MyExtension
        public: false
        tags:
            - { name: oro_message_queue.consumption.extension, persistent: true }
```

Also if an extension is marked as persistent, but it is required to reset some internal state during resetting
of the container, the extension can implement [ResettableExtensionInterface](../../Consumption/Extension/ResettableExtensionInterface.php).

Cache state
-----------

The loading of some types of caches may be quite expensive operation. This is the reason why some cache providers
was added to the `persistent_services` list to prevent removing them from the container after processing of a message.

To synchronize such caches between different processes the [CacheState](../../Consumption/CacheState.php) service
is used. The method `renewChangeDate` should be called after a cache is changed. The method `getChangeDate`
returns the last modification time of a cache.

The [InterruptConsumptionExtension](../../Consumption/Extension/InterruptConsumptionExtension.php) uses the `CacheState`
service to check whether a cache is changed. If this happened, the consumer is interrupted after processing
a current message, so the new instance of the consumer will work with the correct cache.
