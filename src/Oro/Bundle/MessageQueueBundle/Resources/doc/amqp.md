# AMQP (RabbitMQ) Transport

Rabbit MQ provides better and faster messages delivery than DBAL. We should prefer to use it if possible.

### Options

```yaml
oro_message_queue:
  transport:
    default: 'amqp'
    amqp:
        host: 'localhost' 
        port: '5672' 
        user: 'guest' 
        password: 'guest' 
        vhost: 'oro' 
```

### RabbitMQ plugins

We need to enable some RabbitMQ plugins to ensure the transport works properly (the versions numbers could differ):

```
[e*] amqp_client                       3.6.5 
[e*] mochiweb                          2.13.1 
[E*] rabbitmq_delayed_message_exchange 0.0.1
[E*] rabbitmq_management               3.6.5
[e*] rabbitmq_management_agent         3.6.5
[e*] rabbitmq_web_dispatch             3.6.5
[e*] webmachine                        1.10.3
```

The plugin `rabbitmq_management` is needed to manage the RabbitMQ queues via the web interface. Please see 
[the manual](https://www.rabbitmq.com/management.html) for the details.

The plugin `rabbitmq_delayed_message_exchange` is necessarily needed for the consumer proper work. The exception

```
[error] Consuming interrupted by exception. "COMMAND_INVALID - unknown exchange type 'x-delayed-message'"

                                               
  [PhpAmqpLib\Exception\AMQPRuntimeException]  
  Broken pipe or closed connection   
```
  
shows that the plugin is missing. 
  
To download it use a command like

```
curl http://www.rabbitmq.com/community-plugins/v3.6.x/rabbitmq_delayed_message_exchange-0.0.1.ez > {RABBITMQ_HOME}/plugins/rabbitmq_delayed_message_exchange-0.0.1.ez
```
  
To enable it use the command
  
```
rabbitmq-plugins enable --offline rabbitmq_delayed_message_exchange
```


[More about RabbitMQ plugins](https://www.rabbitmq.com/community-plugins.html)

[More about RabbitMQ plugins management](https://www.rabbitmq.com/plugins.html)
  
