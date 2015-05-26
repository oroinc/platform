OroSyncBundle
=============

Bundle adds support of websocket communications. Based on JDareClankBundle.

## Configuration ##
Set port and host (optional) for websocket server in parameters.yml
``` yaml
    websocket_bind_address:  0.0.0.0
    websocket_bind_port:     8080
    websocket_frontend_host: "*"
    websocket_frontend_port: 8080
    websocket_backend_host:  "*"
    websocket_backend_port:  8080
```

Since Clank server is running as a service, there are three host:port pairs for configuration:
- `websocket_bind_port` and `websocket_bind_address` specify port and address to which the Clank server binds on startup and waits for incoming requests. By default (0.0.0.0), it listens to all addresses on the machine
- `websocket_backend_port` and `websocket_backend_host` specify port and address to which the application should connect (PHP). By default ("*"), it connects to 127.0.0.1 address.
- `websocket_frontend_port` and `websocket_frontend_host` specify port and address to which the browser should connect (JS). By default ("*"), it connects to host specified in the browser.



Add the following to your app/config.yml
``` yaml
clank:
    web_socket_server:
        port:                 %websocket_bind_port%          # The port the socket server will listen on
        host:                 %websocket_bind_address%       # (optional) The host ip to bind to
    session_handler:          session.handler.pdo            # Any session handler except native (files)
    periodic:
        -
            service:          "oro_wamp.db_ping"
            time:             60000                          # the time in milliseconds between the "tick" function being called

twig:
    globals:
        ws:
            port:             "%websocket_frontend_port%"    # Websocket port used in JS
            host:             "%websocket_frontend_host%"    # Websocket host used in JS

framework:
    session:
        handler_id:           session.handler.pdo

# session handler config (PDO)
services:
    doctrine.dbal.default.wrapped_connection:
        factory_service:      doctrine.dbal.default_connection
        factory_method:       getWrappedConnection
        class:                PDO
    session.handler.pdo:
        class:                Symfony\Component\HttpFoundation\Session\Storage\Handler\PdoSessionHandler
        arguments:
            - "@doctrine.dbal.default.wrapped_connection"
            -
              db_table:       oro_session
              db_id_col:      id
```

## Usage ##
You should be able to run this from the root of your symfony installation:

``` bash
php app/console clank:server
```

If everything is successful, you will see something similar to the following:

``` bash
Starting Clank
Launching Ratchet WS Server on: *:8080
```

This means the websocket server is now up and running!

Other documentation could be found [here](https://github.com/JDare/ClankBundle#resources).
