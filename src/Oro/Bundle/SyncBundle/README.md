OroSyncBundle
=============

Bundle adds support of websocket communications. Based on [JDareClankBundle](https://github.com/JDare/ClankBundle).

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

Instead of specifying all 3 sets of host:port parameters, it is possible to use fallback parameters `websocket_host` and `websocket_port`, which will be used for any host or port that is not set explicitly.

## Usage ##
You should be able to run this from the root of your symfony installation:

``` bash
php app/console clank:server
```

If everything is successful, you will see something similar to the following:

``` bash
Starting Clank
Launching Ratchet WS Server on: 0.0.0.0:8080
```

This means the websocket server is now up and running!
