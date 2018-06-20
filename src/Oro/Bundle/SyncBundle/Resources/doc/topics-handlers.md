Topics and handlers
===================

Table of content
----------------
- [Overview](#overview)
- [Routing](#routing)
- [Topic handlers](#topic-hanlders)

Overview
--------
Websocket server works under Web Application Messaging Protocol (WAMP) which is intended to provide developers with
the facilities they need to handle messaging between components, i.e. backend and frontend. Websocket server in Oro
application utilizes Publish & Subscribe (PubSub) pattern, where one component subscribes to a topic, and another component
publishes to this topic. Websocket server router, by calling topic handlers, distributes events to all subscribers.

Hence, if one wants to define a topic, he should declare a route for it and create a topic handler.

Routing
-------
In order to declare a route for topic, create `websocket_routing.yml` file in `Resources/config/oro/` directory of your
bundle. Fill it with the following contents:

```yml
oro_sync.ping:                      # unique machine name of your topic in format "%extension_alias%.topic_name"
    channel: 'oro/ping'             # url of your topic channel
    handler:
        callback: 'oro_sync.ping'   # machine name of topic handler
```

You can declare parameterized routes as well, e.g.:

```yml
oro_email.event:
    channel: 'oro/email_event/{user_id}/{organization_id}'
    handler:
        callback: 'oro_email.event'
    requirements:
        user_id:
            pattern: '\d+' # regular expression
        organization_id:
            pattern: '\d+'
```

You will be able to get parameters using `getAttributes()` from `WampRequest $request` argument in your topic handler.
You can find more information about routing in the documentation of GosWebSocketBundle.

Topic handlers
--------------
Topic handler is called by websocket server router whenever occurs one of these events:
* client subscribed
* client unsubscribed
* client published a message

Each topic handler, according to its logic, decides what to do with the occurred event. For example, in `onSubscribe()`
we can decide whether to allow subscription, and in `onPublish()` we can either broadcast given message to all
subscribers or just to restricted list.

Topic handler must implement `Gos\Bundle\WebSocketBundle\Topic\TopicInterface` and be declared as a service with tag
`gos_web_socket.topic`, e.g.:

```yml
    oro_sync.topic.websocket_ping:
        class: 'Oro\Bundle\SyncBundle\Topic\WebsocketPingTopic'
        arguments:
            - 'oro_sync.ping'
            - '@logger'
            - '%oro_sync.websocket_ping.interval%'
        tags:
            - { name: gos_web_socket.topic }
```

Method `getName()` of topic handler must return its machine name which is used in `websocket_routing.yml` file for
`handler.callback` configuration option.

OroSyncBundle provides an abstract class `Oro\Bundle\SyncBundle\Topic\AbstractTopic` and 2 out-of-box implementations
of topic handlers for common purposes:
* `Oro\Bundle\SyncBundle\Topic\BroadcastTopic` - topic handler that broadcasts to all subscribers each published
    message. Needed for such simple topics like `oro_sync.maintenance` which just informs about maintenance mode
    activation.
* `Oro\Bundle\SyncBundle\Topic\SecuredTopic` - topic handler that checks if client is allowed to subscribe to topic.
    Broadcasts to all subscribers each published message. Needed for such topics like `oro_email.event` which informs
    users about new emails.

So, if your topic handler is not intended to contain complex logic, you can use existing handlers, e.g.:

```
    oro_sync.topic.maintenance:
        class: 'Oro\Bundle\SyncBundle\Topic\BroadcastTopic'
        arguments:
            - 'oro_sync.maintenance'
        tags:
            - { name: gos_web_socket.topic }
```
