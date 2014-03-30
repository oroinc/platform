OroPlatformBundle
=================

The Oro Platform version holder and maintenance mode support.

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