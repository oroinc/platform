OroPlatformBundle
=================

The Oro Platform version holder, maintenance mode support and lazy services functionality.

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
