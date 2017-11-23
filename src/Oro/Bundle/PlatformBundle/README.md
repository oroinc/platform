OroPlatformBundle
=================

The Oro Platform version holder, maintenance mode support, lazy services functionality and provide easy way to add application configuration settings from any bundle.


## Table of Contents ##
 - [Maintenance mode](#maintenance-mode)
 - [Lazy services](#lazy-services)
 - [Add application configuration settings from any bundle](#add-application-configuration-settings-from-any-bundle)
 - [Optional Doctrine listeners](#optional-doctrine-listeners)
 - [Lazy Doctrine listeners](#lazy-doctrine-listeners)


## Maintenance mode ##
To use maintenance mode functionality bundle provides `oro_platform.maintenance` service.

``` php
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

class AcmeController extends Controller
{
    public function indexAction()
    {
        // check if maintenance mode is on
        if ($this->get('oro_platform.maintenance')->isOn()) {
            // ...
        }

        // ...
    }

    /**
     * @Route("/maintenance/{mode}", name="acme_maintenance", requirements={"mode"="on|off"})
     */
    public function maintenanceAction($mode = 'on')
    {
        // switch maintenance mode on/off
        if ('on' == $mode) {
            $this->get('oro_platform.maintenance')->on();
        } else {
            $this->get('oro_platform.maintenance')->off();
        }

        // ...
    }
}
```

In maintenance mode all cron jobs disabled for execution.

Other documentation could be found [here](https://github.com/lexik/LexikMaintenanceBundle/blob/master/Resources/doc/index.md).


## Lazy services ##

Lazy service - it's a service that will be used all over the system wrapped inside lazy loading proxy. It allows to
initialize such services not during injecting, but when it will be requested in the first time. Symfony provide
functionality to use [lazy services](http://symfony.com/doc/2.3/components/dependency_injection/lazy_services.html)
out of the box.

For own bundles services must be marked as lazy in service declaration by adding additional key "lazy" set to true.
For example:
```
oro_notification.event_listener.email_notification_service:
    class: %oro_notification.event_listener.email_notification_service.class%
    arguments:
        - @doctrine.orm.entity_manager
    lazy: true
```

For external bundles their services can be marked as lazy using file _/Resources/config/oro/lazy\_services.yml_ in each
bundle. This file should contain plain list of service names under key "lazy_services". For example:
```
lazy_services:
    - assetic.asset_manager
    - knp_menu.renderer.twig
    - templating
    - twig
    - templating.engine.twig
    - twig.controller.exception
```


## Add application configuration settings from any bundle ##

Sometime you need to add some settings to the application configuration from your bundle. For instance a bundle can implement new data type for Doctrine. The more native way to register it is to change _app/config.yml_. But it is the better way to achieve the same result if your bundle is used in ORO Platform. In this case you just need to add _app.yml_ in _Resources/config/oro_ directory of your bundle and the platform will add all setting from this file to the application configuration. The format of _app.yml_ is the same as _app/config.yml_.
The following example shows how `money` data type can be registered:

``` yaml
doctrine:
    dbal:
        types:
            money: Oro\Bundle\EntityBundle\Entity\Type\MoneyType
```

Please note that setting added through _app.yml_ can be overwrote in _app/config.yml_. So, you can consider settings in _app.yml_ as default ones.


## Optional Doctrine listeners ##

Doctrine listeners can be a very slow processes. And during console command execution, you can disable this listeners.

Each console command have additional option `disabled_listeners`. Using this option, you can disable some of doctrine listeners.

As value, this option takes `all` string or array of optional doctrine listener services. In the first case, will be disabled all optional listeners. In the second case, will be disabled only the specified listeners. For example:

```
 app/console some.command --disabled_listeners=first_listener --disabled_listeners=second_listener
```

In this case, command will be run with disabled doctrine listeners: first_listener and second_listener.

See the list of optional listeners you can by run command `oro:platform:optional-listeners`.

To mark your doctrine listener as optional, your listener must implement `Oro\Bundle\PlatformBundle\EventListener\OptionalListenerInterface` interface and set skips in your code if $enabled = false.

## Lazy Doctrine listeners ##

Doctrine [Event Listeners](https://symfony.com/doc/current/doctrine/event_listeners_subscribers.html)
and [Entity Listeners](https://symfony.com/doc/current/bundles/DoctrineBundle/entity-listeners.html)
can have dependencies to other services with a lot of dependencies. This can lead a significant impact on
performance of each request, because all of these services need to be fetched from the service container
(and thus be instantiated) every time when any operation with Entity Manager is performed.

To solve this issue the Symfony provides an ability to mark the listeners as lazily loaded. For details see
[Lazy loading for Event Listeners](https://symfony.com/doc/current/doctrine/event_listeners_subscribers.html#lazy-loading-for-event-listeners).
But it is easy to forget about this, especially in a big project with a lot of listeners. This is the reason why in
the Oro Platform all the listeners are marked as lazily loaded. But if needed, you can remove the lazy loading
for your listeners by adding `lazy: false` to `doctrine.event_listener` or `doctrine.orm.entity_listener` tags. E.g.:

```yaml
services:
    acme.event_listener:
        class: AppBundle\EventListener\DoctrineEventListener
        tags:
            - { name: doctrine.event_listener, event: postPersist, lazy: false }
    acme.entity_listener:
        class: AppBundle\EventListener\DoctrineEntityListener
        tags:
            - { name: doctrine.orm.entity_listener, entity: AppBundle\Entity\MyEntity, event: postPersist, lazy: false }
```
