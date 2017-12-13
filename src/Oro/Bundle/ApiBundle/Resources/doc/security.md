# Configure Stateless Security Firewalls

Symfony allows creating stateless firewalls. In this case, the security token is not serialized for a session.

However, when API calls are utilized in AJAX requests from the UI, the user's token data from the current session must be used instead of the firewall credentials (e.g. WSSE headers). To do this, the firewall should have the [context](http://symfony.com/doc/current/reference/configuration/security.html#firewall-context) parameter with the context name that the system can use to authenticate the user.

**Example:**

```yml
security:
    firewalls:
        some_stateless_firewall_with_AJAX_requests:
            stateless: true
            context:   main
            # ...
```
