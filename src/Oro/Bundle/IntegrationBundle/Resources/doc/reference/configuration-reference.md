#Configuration reference

- [Channel type definition](#channel-type-definition)
- [Transport definition](#transport-definition)
- [Connector definition](#connector-definition)

##Channel type definition

**Channel type** - type of application/service to connect.
**Channel** - and instance of configured channel type with enabled connectors.

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

Integration type might also bring icon that will be shown in type selector. For this purposes type class should implements
`Oro\Bundle\IntegrationBundle\Provider\IconAwareIntegrationInterface` and method `getIcon()` should return valid path to image
for symfony assets helper.

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

**Channel connector** is responsible to bring data in and define compatible channel types. Examples: Magento
customers data connector, Magento catalog data connector.

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
