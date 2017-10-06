OroSyncBundle
=============

Bundle adds support of websocket communications. Based on [JDareClankBundle](https://github.com/JDare/ClankBundle).

## Configuration of regular (not secure, WS) connection ##

Set host, port and path (optional) for websocket server in parameters.yml
``` yaml
    websocket_bind_address:  0.0.0.0
    websocket_bind_port:     8080
    websocket_frontend_host: "*"
    websocket_frontend_port: 8080
    websocket_frontend_path: ""
    websocket_backend_host:  "*"
    websocket_backend_port:  8080
    websocket_backend_path:  ""
```

Since Clank server is running as a service, there are three host:port pairs for configuration:
- `websocket_bind_port` and `websocket_bind_address` specify port and address to which the Clank server binds on startup and waits for incoming requests. By default (0.0.0.0), it listens to all addresses on the machine
- `websocket_backend_port` and `websocket_backend_host`, `websocket_backend_path` specify port and address (`websocket_backend_host` plus `websocket_backend_path` URI) to which the application should connect (PHP). By default ("*"), it connects to 127.0.0.1 address.
- `websocket_frontend_port` and `websocket_frontend_host`, `websocket_backend_path` specify port and address (`websocket_frontend_host` plus `websocket_backend_path` URI) to which the browser should connect (JS). By default ("*"), it connects to host specified in the browser.

Instead of specifying all 3 sets of host:port parameters, it is possible to use fallback parameters `websocket_host` and `websocket_port`, which will be used for any host or port that is not set explicitly.

## Configuration of secure (SSL/WSS) connection ##

Currently direct backend WebSocket SSL/WSS connections are not supported.

To achieve WSS connection for your websocket communication on frontend you should configure additional reverse proxy before Clank server.
Example configuration provided below.

Set websocket settings in parameters.yml
``` yaml
    websocket_bind_address:  0.0.0.0
    websocket_bind_port:     8080
    websocket_frontend_host: "*"
    websocket_frontend_port: 443
    websocket_frontend_path: "ws"
    websocket_backend_host:  "*"
    websocket_backend_port:  8080
    websocket_backend_path:  ""
```

NGINX server configuration: 
```
server {
    # This is your reqular configuration for SSL connections to website
    listen 443 ssl;
    server_name example.com www.example.com
    
    ssl_certificate_key /etc/ssl/private/example.com.key;
    ssl_certificate /etc/ssl/private/example.com.crt.fullchain;
    ssl_protocols TLSv1.2;
    ssl_ciphers EECDH+AESGCM:EDH+AESGCM:AES2;
    
    # ...
    # ... Other website instructions here ...
    # ...
    
    # You need to add additional "location" section for Websockets requests handling
    location /ws {
        # redirect all traffic to localhost:8080;
        proxy_set_header Host $http_host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-NginX-Proxy true;
        proxy_set_header X-Forwarded-Proto $scheme;

        proxy_pass http://127.0.0.1:8080/;
        proxy_redirect off;
        proxy_read_timeout 86400;

        # enables WS support
        proxy_http_version 1.1;
        proxy_set_header Upgrade $http_upgrade;
        proxy_set_header Connection "upgrade";

        # prevents 502 bad gateway error
        proxy_buffers 8 32k;
        proxy_buffer_size 64k;

        reset_timedout_connection on;

        error_log /var/log/nginx/oro_wss_error.log;
        access_log /var/log/nginx/oro_wss_access.log;
    }
     
    # ...
    
    error_log /var/log/nginx/oro_https_error.log;
    access_log /var/log/nginx/oro_https_access.log;
 }
```

## Usage ##
You should be able to run this from the root of your symfony installation:

``` bash
php app/console clank:server
```

If everything is successful, you will see something similar to the following:

``` bash
Starting Clank
Launching Ratchet WS Server on: 127.0.0.1:8080
```

This means the websocket server is now up and running!

## Content Outdating ##

* [Content Outdating Notifications](./Resources/doc/content_outdating.md)
* [Mediator Handlers](./Resources/doc/mediator-handlers.md)
