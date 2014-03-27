OroIntegrationBundle
====================

Integration bundle provides abstraction for channels, transports and connectors. Those objects are responsible for
integration between application and third party applications or services (e.g. ECommerce stores, ERP systems etc..).
General purpose is to allow developers to create integration bundles and provide basic UI for its configuration.


- [Channel type definition](#channel-type-definition)
- [Transport definition](#transport-definition)
- [Connector definition](#connector-definition)



##Channel type definition
**Channel types** - allows to define channel configuration. Example: Magento channel type that allows to define API
credentials.
**Channel** - and instance of configured channel type with enabled connectors. Attributes: name, settings.

Responsibility of channel is to split on groups transport/connectors by third party application type.
To define you own channel type developer should create class that will implement
`Oro\Bundle\IntegrationBundle\Provider\ChannelInterface` and register it as service with `oro_integration.channel` tag
that will contains `type` key, it's should be unique.

####Example:
``` yaml
    acme.demo_integration.provider.prestashop.channel:
        class: %acme.demo_integration.provider.prestashop.channel.class%
        tags:
            - { name: oro_integration.channel, type: presta_shop }
```

##Transport definition
Responsibility of **transport** is communicate connector and channel, it should perform read/write operations to third
party systems.
To define you own transport developer should create class that will implement
`Oro\Bundle\IntegrationBundle\Provider\TransportInterface` and register it as service with `oro_integration.transport`
tag that will contains `type` key, it's should be unique and `channel_type` key that shows for what channel type it
could be used.

####Example:
``` yaml
    acme.demo_integration.provider.db_transport:
        class: %acme.demo_integration.provider.db_transport.class%
        tags:
            - { name: oro_integration.transport, type: db, channel_type: presta_shop }
```

##Connector definition
**Channel connector** that is responsible to bring data in and define compatible channel types. Examples: Magento
customers data connector, Magento catalog data connector for PIM, Magento catalog data connector for CRM etc..

To define you own connector developer should create class that will implement
`Oro\Bundle\IntegrationBundle\Provider\ConnectorInterface` and register it as service with `oro_integration.connector`
tag that will contains `type` key, it's should be unique for the channel and `channel_type` key that shows for what
channel type it could be used.

####Example:
``` yaml
    acme.demo_integration.provider.prestashop_product.connector:
        class: %acme.demo_integration.provider.prestashop_product.connector.class%
        tags:
            - { name: oro_integration.connector, type: product, channel_type: presta_shop }
```
