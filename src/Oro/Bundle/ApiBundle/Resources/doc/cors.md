# CORS Configuration

By default the [Cross-Origin Resource Sharing (CORS)](https://www.w3.org/TR/cors/) is disabled for REST API.
To enable it you need to configure a list of origins that are allowed to access your REST API resources
via `Resources/config/oro/app.yml` in any bundle or `config/config.yml` of your application, e.g.:

```yaml
oro_api:
    cors:
        allow_origins:
            - 'https://example.com'
```

You can also configure other CORS options. Here is the default configuration:

```yaml
oro_api:
    cors:
        # The amount of seconds the user agent is allowed to cache CORS preflight requests.
        preflight_max_age: 600

        # The list of origins that are allowed to send CORS requests.
        allow_origins: []

        # Indicates whether CORS request can include user credentials.
        # This option determines whether the "Access-Control-Allow-Credentials" response header
        # should be passed within CORS requests.
        allow_credentials: false

        # The list of headers that are allowed to send by CORS requests.
        # This option specifies a list of headers that are sent
        # in the "Access-Control-Allow-Headers" response header of CORS preflight requests
        allow_headers: []

        # The list of headers that can be exposed by CORS responses.
        # This option specifies a list of headers that are sent
        # in the "Access-Control-Expose-Headers" response header of CORS requests
        expose_headers: []
```
