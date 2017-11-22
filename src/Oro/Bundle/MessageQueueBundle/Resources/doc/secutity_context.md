Security Context in consumer
============================

Passing the security context from the producer to the consumer
--------------------------------------------------------------

By default, if a code that sent a message to the message queue works in some security context, or in other words,
a [security token](http://api.symfony.com/master/Symfony/Component/Security/Core/Authentication/Token/TokenInterface.html)
exists in the [token storage](http://api.symfony.com/master/Symfony/Component/Security/Core/Authentication/Token/Storage/TokenStorageInterface.html),
the security token is serialized and added to the message. When the consumer processes this message the security token
is extracted from the message and added to the token storage on the consumer side. This simple approach allows
to not care about the correct security context for the most of messages.

But sometimes, this behaviour need to be changed for some types of messages. The following sections describe how
this can be achieved.

Adding custom security token to the message
-------------------------------------------

In case if it is required to process a message in the security context different than the producer's security context,
you can add the security token to the message manually. The added token can be an instance of class implements
[TokenInterface](http://api.symfony.com/master/Symfony/Component/Security/Core/Authentication/Token/TokenInterface.html),
a string represents already serialized token or *null* in case if the message should be processed without
the security context. To add the security token the `oro.security.token` property should be used.
Here is an example:

```php
use Oro\Bundle\MessageQueueBundle\Security\SecurityAwareDriver;

$message->setProperty(SecurityAwareDriver::PARAMETER_SECURITY_TOKEN, $token);
```

Security agnostic topics
------------------------

If some type of messages should be always processed without the security context, they should be added to the list of
security agnostic topics. This list can be configured by *Resources/config/oro/app.yml* or the application
configuration file. Here is an example:

```yaml
oro_message_queue:
    security_agnostic_topics:
        - 'oro.message_queue.job.calculate_root_job_status'
```

Please note that for such messages the security token is never added to the message. Moreover, even if the security
token was added to the message manually it will be removed before the message is sent to the message queue.

Security agnostic processors
----------------------------

Sometimes, mostly by performance reasons, it is required to execute a message queue processor without the security
context even if the processed message contains the security token. The typical use case is routing processors.
These processors just forward a message to the destination processor and there is no sense to spend the processor time
to deserialize the security token because it is never used in such type of processors.
Here is an example how to add a processor to the list of security agnostic processors using
*Resources/config/oro/app.yml* or the application configuration file:

```yaml
oro_message_queue:
    security_agnostic_processors:
        - 'oro_message_queue.client.route_message_processor'
```

Implementation details
----------------------

The adding the security token to a message on the producer side is performed by
[SecurityAwareDriver](../../Security/SecurityAwareDriver.php).

The extracting the security token from a message and adding it to the token storage on the consumer side
is performed by [SecurityAwareConsumptionExtension](../../Security/SecurityAwareConsumptionExtension.php).

The serialization of security tokens is performed by [TokenSerializer](../../../SecurityBundle/Authentication/TokenSerializer.php).
In case if a new type of token is added and it cannot be serialized by this class, you can implement own serializer
and register it as a decorator for the `oro_security.token_serializer` service.
