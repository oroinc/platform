Origin Checking
===============

When connection with websocket server is established it checks `Origin` header to ensure that it contains domain which
is in the list of allowed origins to eliminate Cross-Site WebSocket Hijacking (CSWSH) attacks. Connections with not allowed origins
will be rejected by websocket server.

The list of allowed origins is not directly configurable via UI. By default, it contains host specified in
`System Configuration / General Setup / Application Settings / URL / Application URL`.

How to Customize
----------------

Origins are collected by `oro_sync.authentication.origin.origin_provider_chain` which in its turn calls origins
providers. In order to add custom origin, you should create a provider implementing
 `Oro\Bundle\SyncBundle\Authentication\Origin\OriginProviderInterface` and declare it as a service with tag `oro_sync.origin_provider`, e.g.

``` yaml
    oro_sync.authentication.origin.application_origin_provider:
        class: Oro\Bundle\SyncBundle\Authentication\Origin\ApplicationOriginProvider
        public: false
        arguments:
            - '@oro_config.global'
            - '@oro_sync.authentication.origin.extractor'
        tags:
            - { name: oro_sync.origin_provider }
```

Backend Websocket Client
------------------------

As origin checking is not needed when connecting from backend, websocket client `oro_sync.websocket_client` always
connects with origin set to `127.0.0.1`.

Notes
-----
Origins `localhost` and `127.0.0.1` are automatically added as allowed origins.
